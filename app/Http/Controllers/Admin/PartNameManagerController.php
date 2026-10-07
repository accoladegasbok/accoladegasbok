<?php
// FILE: app/Http/Controllers/Admin/PartNameManagerController.php
//
// Part Names Manager.
//
//  - Supervisor and above: see the list, ADD new names, switch a name on or
//    off for the harvest checklist.
//  - Admin only: MERGE, RENAME and DELETE — these retag every matching part,
//    so they stay with admin.
//
// New names go into part_terminology, which is the shared list read by
// Manual Add, Consumables and (when "show on harvest checklist" is on) the
// harvest checklist. One addition here serves all of them.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use App\Support\StaffRole;

class PartNameManagerController extends Controller
{
    /** Categories offered when adding a name (vehicle + non-vehicle). */
    public const CATEGORY_OPTIONS = [
        'General', 'Engine', 'Transmission', 'Electrical', 'Body', 'Suspension',
        'Cooling', 'Brakes', 'Interior', 'Airbag', 'Seat', 'Fuel', 'Exhaust', 'Wheels',
        'Consumable', 'Electronics', 'Computers', 'Other',
    ];

    private function requireAdmin()
    {
        if (!StaffRole::isAdmin()) {
            abort(403, 'Admin only.');
        }
    }

    private function requireSupervisor()
    {
        if (!StaffRole::isSupervisorOrAbove()) {
            abort(403, 'Supervisor or above only.');
        }
    }

    // GET /admin/part-names — list distinct part names in use, with counts
    public function index(Request $request)
    {
        $this->requireSupervisor();

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

        $hasFlag = Schema::hasColumn('part_terminology', 'harvest_checklist');
        $termQuery = DB::table('part_terminology')->select(
            'id', 'category', 'standard_name',
            $hasFlag ? 'harvest_checklist' : DB::raw('0 as harvest_checklist')
        );
        if ($q) $termQuery->where('standard_name', 'like', "%{$q}%");
        $termRows = $termQuery->get()->keyBy(fn($r) => strtolower(trim($r->standard_name)));

        // Names that are always rows on the harvest checklist (built in).
        $builtIn = HarvestController::builtInLabels();

        // none = not in the shared list | built-in | on | off
        $harvestState = function (string $key, $termRow) use ($builtIn) {
            if (in_array($key, $builtIn, true)) return 'built-in';
            if (!$termRow) return 'none';
            return $termRow->harvest_checklist ? 'on' : 'off';
        };

        $merged = [];
        foreach ($invRows as $key => $row) {
            $merged[$key] = (object) [
                'part_name'      => $row->part_name,
                'part_count'     => $row->part_count,
                'total_stock'    => $row->total_stock,
                'in_taxonomy'    => isset($termRows[$key]),
                'harvest_state'  => $harvestState($key, $termRows[$key] ?? null),
            ];
        }
        foreach ($termRows as $key => $row) {
            if (!isset($merged[$key])) {
                $merged[$key] = (object) [
                    'part_name'     => $row->standard_name,
                    'part_count'    => 0,
                    'total_stock'   => 0,
                    'in_taxonomy'   => true,
                    'harvest_state' => $harvestState($key, $row),
                ];
            }
        }

        $names = collect($merged)->sortBy(fn($r) => strtolower($r->part_name))->values();

        return view('admin.part-names.index', [
            'names'           => $names,
            'q'               => $q,
            'isAdmin'         => StaffRole::isAdmin(),
            'categoryOptions' => self::CATEGORY_OPTIONS,
        ]);
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
        $this->requireSupervisor();

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
        $name     = trim($request->name);

        // Case-insensitive, across ALL categories — "alternator" and
        // "Alternator" must not become two entries.
        $existing = DB::table('part_terminology')
            ->whereRaw('LOWER(standard_name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            return back()->with('error', "\"{$name}\" already exists (under {$existing->category}).");
        }

        $row = [
            'category'       => $category,
            'standard_name'  => $name,
            'aces_pies_note' => null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ];
        // Show on the harvest checklist too? (ticked by default on the form)
        if (Schema::hasColumn('part_terminology', 'harvest_checklist')) {
            $row['harvest_checklist'] = $request->boolean('harvest_checklist') ? 1 : 0;
        }
        DB::table('part_terminology')->insert($row);

        $where = ($row['harvest_checklist'] ?? 0)
            ? 'Manual Add, Consumables and the harvest checklist'
            : 'Manual Add and Consumables';

        return redirect()->route('admin.part-names.index')
            ->with('success', "\"{$name}\" added under {$category} — now available on {$where}.");
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

    // POST /admin/part-names/add-to-taxonomy — one-click "activate"
    // for a name already used on real inventory but missing from the
    // standardized taxonomy (shows "NO" in the dropdown column).
    // Exact name, no retyping — avoids the typo risk of re-adding it
    // manually through the Add form.
    public function addToTaxonomy(Request $request)
    {
        $this->requireSupervisor();

        $request->validate([
            'part_name' => 'required|string|max:150',
            'category'  => 'nullable|string|max:60',
        ]);

        $category = $request->category ?: 'General';

        if (DB::table('part_terminology')->whereRaw('LOWER(standard_name) = ?', [mb_strtolower($request->part_name)])->exists()) {
            return back()->with('error', "\"{$request->part_name}\" is already in the dropdown list.");
        }

        $row = [
            'category'       => $category,
            'standard_name'  => $request->part_name,
            'aces_pies_note' => null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ];
        if (Schema::hasColumn('part_terminology', 'harvest_checklist')) {
            $row['harvest_checklist'] = 0;
        }
        DB::table('part_terminology')->insert($row);

        return redirect()->route('admin.part-names.index')
            ->with('success', "\"{$request->part_name}\" is now in the dropdown.");
    }

    // POST /admin/part-names/toggle-harvest — switch an extra name on/off the
    // harvest checklist. Built-in checklist rows can't be switched off here.
    public function toggleHarvest(Request $request)
    {
        $this->requireSupervisor();

        $request->validate(['part_name' => 'required|string|max:150']);

        if (!Schema::hasColumn('part_terminology', 'harvest_checklist')) {
            return back()->with('error', 'Run the latest migrations first (harvest_checklist column is missing).');
        }

        if (in_array(mb_strtolower($request->part_name), HarvestController::builtInLabels(), true)) {
            return back()->with('error', "\"{$request->part_name}\" is a built-in checklist row and always shows.");
        }

        $term = DB::table('part_terminology')->whereRaw('LOWER(standard_name) = ?', [mb_strtolower($request->part_name)])->first();
        if (!$term) {
            return back()->with('error', 'Add the name to the dropdown list first.');
        }

        $new = $term->harvest_checklist ? 0 : 1;
        DB::table('part_terminology')->where('id', $term->id)->update(['harvest_checklist' => $new, 'updated_at' => now()]);

        return redirect()->route('admin.part-names.index')
            ->with('success', "\"{$term->standard_name}\" " . ($new ? 'now shows' : 'no longer shows') . ' on the harvest checklist.');
    }
}
