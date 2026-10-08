<?php
// FILE: app/Services/AiFitmentService.php
//
// Turns ONE staff-approved AI suggestion into real fitment: the part joins an interchange group
// (a new one is created if it has none) and the suggested vehicle is added to that group.
// Called only from the AI Fitment Review screen, after the second review step.

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AiFitmentService
{
    public function __construct(private InterchangeService $interchange) {}

    /** What the review screen should warn about for this suggestion (it never blocks, it makes staff look). */
    public function warnings(object $s): array
    {
        $w = [];
        if ($s->confidence === 'low')    $w[] = ['level' => 'high', 'text' => 'LOW confidence — the AI is guessing. Check an OEM number or a physical fit before approving.'];
        if ($s->confidence === 'medium') $w[] = ['level' => 'mid',  'text' => 'Medium confidence — verify before approving.'];

        $span = (int) $s->suggested_year_to - (int) $s->suggested_year_from;
        if ($span > 8)  $w[] = ['level' => 'mid', 'text' => "Wide year range ({$span} years) — body and engine changes often happen inside ranges like this."];
        if ((int) $s->suggested_year_from < 1986 || (int) $s->suggested_year_to < (int) $s->suggested_year_from) {
            $w[] = ['level' => 'high', 'text' => 'The year range looks wrong.'];
        }

        if (!$s->part_id) {
            $w[] = ['level' => 'high', 'text' => 'Not tied to a part — confirm this one from the Compatibility Checker, where you pick the group.'];
            return $w;
        }

        $part = DB::table('parts_inventory')->where('id', $s->part_id)->first();
        if (!$part) { $w[] = ['level' => 'high', 'text' => 'The part no longer exists.']; return $w; }

        if (!$part->interchange_group_id) {
            $w[] = ['level' => 'info', 'text' => 'This part has no fitment group yet — one will be created.'];
        } elseif ($this->vehicleAlreadyInGroup((int) $part->interchange_group_id, $s)) {
            $w[] = ['level' => 'info', 'text' => 'This vehicle is already in the part\'s group — nothing new will be added.'];
        }
        return $w;
    }

    /** @return array{group_id:int, vehicle_row_id:?int, result:string} */
    public function apply(object $s, ?int $staffId): array
    {
        $part = DB::table('parts_inventory')->where('id', $s->part_id)->first();
        if (!$part) throw new \RuntimeException('Part no longer exists.');

        $createdGroup = false;
        $groupId = (int) $part->interchange_group_id;

        if (!$groupId) {
            $groupId = $this->interchange->createGroup(
                $part->part_category, $part->part_name, $this->newGroupCode($part),
                'Created from an AI fitment suggestion, approved by staff.', $staffId
            );
            // The part's own vehicle is always the first entry, exactly like a manually created group.
            $this->interchange->addVehicleToGroup(
                $groupId, $part->brand, $part->model,
                (int) ($part->compat_year_from ?? $part->year_from), (int) ($part->compat_year_to ?? $part->year_to)
            );
            $this->interchange->assignPartToGroup($part->id, $groupId);
            $createdGroup = true;
        }

        if ($this->vehicleAlreadyInGroup($groupId, $s)) {
            return ['group_id' => $groupId, 'vehicle_row_id' => null,
                    'result' => 'Already in the group — nothing added' . ($createdGroup ? ' (new group created)' : '')];
        }

        $rowId = $this->interchange->addVehicleToGroup(
            $groupId, strtoupper($s->suggested_make), strtoupper($s->suggested_model),
            (int) $s->suggested_year_from, (int) $s->suggested_year_to
        );

        $extra = ['ai_suggestion_id' => $s->id];
        if (Schema::hasColumn('part_interchange_vehicles', 'conditions_note')) {
            $codes = trim(($s->engine_code ? "Engine {$s->engine_code}" : '') . ($s->transmission_code ? ' / Trans ' . $s->transmission_code : ''), ' /');
            if ($codes !== '') $extra['conditions_note'] = Str::limit($codes . ' (AI-suggested, staff-approved)', 480, '');
        }
        DB::table('part_interchange_vehicles')->where('id', $rowId)->update($extra);

        $label = DB::table('part_interchange_groups')->where('id', $groupId)->value('group_code');
        return ['group_id' => $groupId, 'vehicle_row_id' => $rowId,
                'result' => ($createdGroup ? 'Added to NEW group ' : 'Added to group ') . $label];
    }

    private function vehicleAlreadyInGroup(int $groupId, object $s): bool
    {
        return DB::table('part_interchange_vehicles')
            ->where('group_id', $groupId)
            ->whereRaw('UPPER(make) = ?', [strtoupper($s->suggested_make)])
            ->whereRaw('UPPER(model) = ?', [strtoupper($s->suggested_model)])
            ->where('year_from', '<=', (int) $s->suggested_year_from)
            ->where('year_to', '>=', (int) $s->suggested_year_to)
            ->exists();
    }

    private function newGroupCode(object $part): string
    {
        $cat = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string) $part->part_category), 0, 3)) ?: 'GRP';
        do {
            $code = "AI-{$cat}-{$part->id}-" . strtoupper(Str::random(4));
        } while (DB::table('part_interchange_groups')->where('group_code', $code)->exists());
        return $code;
    }
}
