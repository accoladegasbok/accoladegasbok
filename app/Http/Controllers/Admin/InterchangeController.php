<?php
// FILE: app/Http/Controllers/Admin/InterchangeController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\InterchangeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class InterchangeController extends Controller
{
    private InterchangeService $interchange;

    public function __construct(InterchangeService $interchange)
    {
        $this->interchange = $interchange;
    }

    // =========================================================
    // POST /admin/interchange/groups — create a new group and
    // immediately assign the current part to it.
    // =========================================================
    public function createGroup(Request $request)
    {
        $request->validate([
            'part_id'      => 'required|exists:parts_inventory,id',
            'group_code'   => 'required|string|max:80|unique:part_interchange_groups,group_code',
            'notes'        => 'nullable|string|max:1000',
            // NEW: when this creation originated from confirming an AI
            // suggestion, thread the suggestion's own ID through so it
            // can be marked confirmed and linked to the resulting
            // group — see ai_suggestions / AI Knowledge Layer.
            'ai_suggestion_id' => 'nullable|integer|exists:ai_suggestions,id',
        ]);

        $part = DB::table('parts_inventory')->where('id', $request->part_id)->first();
        abort_if(!$part, 404);

        $groupId = $this->interchange->createGroup(
            $part->part_category,
            $part->part_name,
            $request->group_code,
            $request->notes,
            Session::get('staff_id')
        );

        // The part's own vehicle is the first entry in the group
        $this->interchange->addVehicleToGroup(
            $groupId,
            $part->brand,
            $part->model,
            (int) ($part->compat_year_from ?? $part->year_from),
            (int) ($part->compat_year_to ?? $part->year_to)
        );

       $this->interchange->assignPartToGroup($part->id, $groupId);

        // NEW: mark the originating AI suggestion confirmed — this is
        // what actually closes the loop on the AI Knowledge Layer: the
        // suggestion's reasoning stays queryable, now linked to the
        // real group it became.
        if ($request->filled('ai_suggestion_id')) {
            DB::table('ai_suggestions')->where('id', $request->ai_suggestion_id)->update([
                'group_id'             => $groupId,
                'review_status'        => 'confirmed',
                'reviewed_by_staff_id' => Session::get('staff_id'),
                'reviewed_at'          => now(),
                'updated_at'           => now(),
            ]);
        }

        // AJAX callers (like the AI-suggestion "+ Confirm" button) get
        // JSON back with the new group_id so they can add further
        // suggestions to the SAME group instantly, without a page reload.
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'group_id' => $groupId,
                'message'  => "Interchange group {$request->group_code} created.",
            ]);
        }

        return redirect()->route('admin.inventory.edit', $part->id)
            ->with('success', "Interchange group {$request->group_code} created and this part assigned to it.");
    }

    // =========================================================
    // POST /admin/interchange/groups/{groupId}/vehicles — add a
    // compatible vehicle to an existing group (research-based addition).
    // =========================================================
    public function addVehicle(Request $request, int $groupId)
    {
        $request->validate([
            'make'       => 'required|string|max:60',
            'model'      => 'required|string|max:80',
            'year_from'  => 'required|integer|min:1986|max:2027',
            'year_to'    => 'required|integer|min:1986|max:2027',
            'part_id'    => 'required|exists:parts_inventory,id', // for redirect back
            'ai_suggestion_id' => 'nullable|integer|exists:ai_suggestions,id',
        ]);

        $this->interchange->addVehicleToGroup(
            $groupId,
            $request->make,
            $request->model,
            $request->year_from,
            $request->year_to
        );

        // NEW: same AI Knowledge Layer close-out as createGroup() above.
        if ($request->filled('ai_suggestion_id')) {
            DB::table('ai_suggestions')->where('id', $request->ai_suggestion_id)->update([
                'group_id'             => $groupId,
                'review_status'        => 'confirmed',
                'reviewed_by_staff_id' => Session::get('staff_id'),
                'reviewed_at'          => now(),
                'updated_at'           => now(),
            ]);
        }

        return redirect()->route('admin.inventory.edit', $request->part_id)
            ->with('success', "Added {$request->make} {$request->model} ({$request->year_from}–{$request->year_to}) to this interchange group.");
    }

    // =========================================================
    // POST /admin/interchange/promote-heuristic — confirm a
    // system-suggested heuristic match and save it as a real group.
    // =========================================================
    public function promoteHeuristic(Request $request)
    {
        $request->validate([
            'part_id'    => 'required|exists:parts_inventory,id',
            'group_code' => 'required|string|max:80|unique:part_interchange_groups,group_code',
        ]);

        $part = DB::table('parts_inventory')->where('id', $request->part_id)->first();
        abort_if(!$part, 404);

        $heuristic = $this->interchange->interchangeFor(
            $part->part_name,
            $part->engine_code_oem,
            $part->transmission_code_oem
        );

        if (!$heuristic['found'] || $heuristic['source'] !== 'auto_heuristic') {
            return back()->with('error', 'No heuristic suggestion available to promote.');
        }

        $groupId = $this->interchange->promoteHeuristicToGroup(
            $part->part_category,
            $part->part_name,
            $request->group_code,
            $heuristic['vehicles'],
            Session::get('staff_id')
        );

        $this->interchange->assignPartToGroup($part->id, $groupId);

        return redirect()->route('admin.inventory.edit', $part->id)
            ->with('success', "Suggestion confirmed and saved as group {$request->group_code}.");
    }

    // =========================================================
    // POST /admin/interchange/parts/{partId}/remove — unlink a part
    // from its interchange group (doesn't delete the group itself).
    // =========================================================
    public function removePart(int $partId)
    {
        DB::table('parts_inventory')->where('id', $partId)->update([
            'interchange_group_id' => null,
            'updated_at'           => now(),
        ]);

        return redirect()->route('admin.inventory.edit', $partId)
            ->with('success', 'Part removed from its interchange group.');
    }

    // =========================================================
    // POST /admin/interchange/parts/{partId}/assign — manually assign
    // this part to an EXISTING group by code (e.g. matching a part
    // someone already set up under a different name).
    // =========================================================
    public function assignExisting(Request $request, int $partId)
    {
        $request->validate(['group_code' => 'required|string|exists:part_interchange_groups,group_code']);

        $group = DB::table('part_interchange_groups')->where('group_code', $request->group_code)->first();
        $this->interchange->assignPartToGroup($partId, $group->id);

        return redirect()->route('admin.inventory.edit', $partId)
            ->with('success', "Assigned to existing group {$request->group_code}.");
    }
}
