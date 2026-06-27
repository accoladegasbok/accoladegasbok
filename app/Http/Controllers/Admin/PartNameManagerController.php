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
}
