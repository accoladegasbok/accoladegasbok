<?php
// FILE: app/Http/Controllers/Admin/PartNameManagerController.php
//
// Admin-only tool to fix part-name inconsistencies in REAL inventory
// data (e.g. "Headlamp" vs "Headlight" used interchangeably across
// different harvest sessions). Merges multiple names into one
// canonical name, re-tagging every affected parts_inventory row.
//
// NOTE: the master "allowed names" whitelist that non-admin staff
// are restricted to (App\Data\PartNames::flat()) is a static PHP
// class, not a database table — so this tool cleans up actual
// inventory data, but adding/removing an entry from that whitelist
// itself still requires editing app/Data/PartNames.php directly and
// redeploying. If you want that whitelist itself to be admin-editable
// without a code deploy, that's a small follow-up (move it into a
// database table) — let me know if you want that built too.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PartNameManagerController extends Controller
{
    private function requireAdmin()
    {
        if (Session::get('staff_role') !== 'admin') {
            abort(403, 'Admin only.');
        }
    }

    // GET /admin/part-names — list distinct part names in use, with counts
    public function index(Request $request)
    {
        $this->requireAdmin();

        $q = trim($request->get('q', ''));

        $query = DB::table('parts_inventory')
            ->select('part_name', DB::raw('COUNT(*) as part_count'), DB::raw('SUM(stock_qty) as total_stock'))
            ->groupBy('part_name')
            ->orderBy('part_name');

        if ($q) {
            $query->where('part_name', 'like', "%{$q}%");
        }

        $names = $query->get();

        return view('admin.part-names.index', compact('names', 'q'));
    }

    // POST /admin/part-names/merge
    // Body: { from_names: ["Headlamp", "Head Lamp"], to_name: "Headlight" }
    public function merge(Request $request)
    {
        $this->requireAdmin();

        $request->validate([
            'from_names'   => 'required|array|min:1',
            'from_names.*' => 'required|string',
            'to_name'      => 'required|string|max:150',
        ]);

        $affected = 0;
        DB::beginTransaction();
        try {
            foreach ($request->from_names as $oldName) {
                if ($oldName === $request->to_name) continue;
                $affected += DB::table('parts_inventory')
                    ->where('part_name', $oldName)
                    ->update(['part_name' => $request->to_name, 'updated_at' => now()]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Merge failed: ' . $e->getMessage());
        }

        return redirect()->route('admin.part-names.index')
            ->with('success', "Merged into \"{$request->to_name}\" — {$affected} part(s) updated.");
    }

    // POST /admin/part-names/rename-one
    // Quick single-name rename without a full merge (still bulk-updates
    // every part currently using that exact name).
    public function renameOne(Request $request)
    {
        $this->requireAdmin();

        $request->validate([
            'old_name' => 'required|string',
            'new_name' => 'required|string|max:150',
        ]);

        $affected = DB::table('parts_inventory')
            ->where('part_name', $request->old_name)
            ->update(['part_name' => $request->new_name, 'updated_at' => now()]);

        return redirect()->route('admin.part-names.index')
            ->with('success', "Renamed \"{$request->old_name}\" → \"{$request->new_name}\" — {$affected} part(s) updated.");
    }

    // POST /admin/part-names/store — add a brand-new canonical name.
    // NEW: writes to part_terminology (the existing standardized
    // taxonomy from harvest/compatibility), not a new table. flat()
    // in PartNames.php merges this in live, so a name added here
    // shows up on the manual-add datalist immediately — no redeploy.
    public function store(Request $request)
    {
        $this->requireAdmin();

        $request->validate([
            'category'      => 'required|string|max:60',
            'standard_name' => 'required|string|max:150',
        ]);

        $exists = DB::table('part_terminology')
            ->where('category', $request->category)
            ->where('standard_name', $request->standard_name)
            ->exists();

        if ($exists) {
            return back()->with('error', "\"{$request->standard_name}\" already exists under {$request->category}.");
        }

        DB::table('part_terminology')->insert([
            'category'       => $request->category,
            'standard_name'  => $request->standard_name,
            'aces_pies_note' => null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return redirect()->route('admin.part-names.index')
            ->with('success', "\"{$request->standard_name}\" added under {$request->category}.");
    }

    // DELETE /admin/part-names/{id} — remove a canonical name. Blocked
    // if any part currently references it, so deleting a name never
    // silently orphans real inventory data.
    public function destroy(int $id)
    {
        $this->requireAdmin();

        $term = DB::table('part_terminology')->where('id', $id)->first();
        if (!$term) {
            return back()->with('error', 'Not found.');
        }

        $inUse = DB::table('parts_inventory')->where('part_terminology_id', $id)->exists();
        if ($inUse) {
            return back()->with('error', "\"{$term->standard_name}\" is in use by existing parts — can't remove it. Merge or rename those parts first.");
        }

        DB::table('part_terminology')->where('id', $id)->delete();

        return redirect()->route('admin.part-names.index')
            ->with('success', "\"{$term->standard_name}\" removed.");
    }
}
