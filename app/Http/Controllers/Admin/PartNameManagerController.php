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

        // FIXED: this used to query parts_inventory ONLY — meaning a
        // name that existed in part_terminology (the standardized
        // taxonomy that Add Parts Manually/Harvest actually read from)
        // but had zero real inventory tagged with it yet was
        // completely invisible on this page. Real unification means
        // showing BOTH sources together, not just one.
        $invQuery = DB::table('parts_inventory')
            ->select('part_name', DB::raw('COUNT(*) as part_count'), DB::raw('SUM(stock_qty) as total_stock'))
            ->groupBy('part_name');
        if ($q) $invQuery->where('part_name', 'like', "%{$q}%");
        $invRows = $invQuery->get()->keyBy(fn($r) => strtolower(trim($r->part_name)));

        $termQuery = DB::table('part_terminology')->select('id', 'category', 'standard_name');
        if ($q) $termQuery->where('standard_name', 'like', "%{$q}%");
        $termRows = $termQuery->get()->keyBy(fn($r) => strtolower(trim($r->standard_name)));

        $merged = [];
        foreach ($invRows as $key => $row) {
            $merged[$key] = (object) [
                'part_name'      => $row->part_name,
                'part_count'     => $row->part_count,
                'total_stock'    => $row->total_stock,
                'in_taxonomy'    => isset($termRows[$key]),
            ];
        }
        foreach ($termRows as $key => $row) {
            if (!isset($merged[$key])) {
                $merged[$key] = (object) [
                    'part_name'   => $row->standard_name,
                    'part_count'  => 0,
                    'total_stock' => 0,
                    'in_taxonomy' => true,
                ];
            }
        }

        $names = collect($merged)->sortBy(fn($r) => strtolower($r->part_name))->values();

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

                // NEW: clear out the old name's taxonomy entry (if any)
                // since it's been merged away — same unification fix
                // as renameOne().
                DB::table('part_terminology')->where('standard_name', $oldName)->delete();
            }

            // Ensure the canonical target name exists in the taxonomy,
            // so it's selectable on Add Parts Manually/Harvest even if
            // none of the merged names happened to have a taxonomy
            // entry already.
            if (!DB::table('part_terminology')->where('standard_name', $request->to_name)->exists()) {
                DB::table('part_terminology')->insert([
                    'category'       => 'General',
                    'standard_name'  => $request->to_name,
                    'aces_pies_note' => null,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Merge failed: ' . $e->getMessage());
        }

        return redirect()->route('admin.part-names.index')
            ->with('success', "Merged into \"{$request->to_name}\" — {$affected} part(s) updated, and the standardized name list is now in sync.");
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

        // NEW: propagate to part_terminology too — this is the actual
        // unification fix. Previously a rename here never touched the
        // standardized taxonomy at all, so Add Parts Manually/Harvest
        // dropdowns kept showing the OLD name forever, permanently out
        // of sync with what this tool just renamed.
        $existingTerm = DB::table('part_terminology')->where('standard_name', $request->old_name)->first();
        if ($existingTerm) {
            DB::table('part_terminology')->where('id', $existingTerm->id)->update([
                'standard_name' => $request->new_name,
                'updated_at'    => now(),
            ]);
        } elseif (!DB::table('part_terminology')->where('standard_name', $request->new_name)->exists()) {
            // No taxonomy entry existed for the old name at all — create
            // one for the new name so it becomes selectable going
            // forward too, not just retroactively relabeled on old parts.
            DB::table('part_terminology')->insert([
                'category'       => 'General',
                'standard_name'  => $request->new_name,
                'aces_pies_note' => null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        return redirect()->route('admin.part-names.index')
            ->with('success', "Renamed \"{$request->old_name}\" → \"{$request->new_name}\" — {$affected} part(s) updated, and the standardized name list is now in sync.");
    }

    // POST /admin/part-names/store — add a brand-new canonical name.
    // NEW: writes to part_terminology (the existing standardized
    // taxonomy from harvest/compatibility), not a new table. flat()
    // in PartNames.php merges this in live, so a name added here
    // shows up on the manual-add datalist immediately — no redeploy.
    public function store(Request $request)
    {
        $this->requireAdmin();

        // FIXED: the real form (admin/part-names/index.blade.php)
        // sends the field as `name`, not `standard_name` — this
        // caused every submission to fail validation silently, with
        // the page showing whatever the PREVIOUS action's flash
        // message happened to be instead of a real error (this view
        // has no @if($errors->any()) block at all, so a failed
        // validation was completely invisible).
        $request->validate([
            'category' => 'nullable|string|max:60',
            'name'     => 'required|string|max:150',
        ]);

        $category = $request->category ?: 'General';

        $exists = DB::table('part_terminology')
            ->where('category', $category)
            ->where('standard_name', $request->name)
            ->exists();

        if ($exists) {
            return back()->with('error', "\"{$request->name}\" already exists under {$category}.");
        }

        DB::table('part_terminology')->insert([
            'category'       => $category,
            'standard_name'  => $request->name,
            'aces_pies_note' => null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return redirect()->route('admin.part-names.index')
            ->with('success', "\"{$request->name}\" added under {$category}.");
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
