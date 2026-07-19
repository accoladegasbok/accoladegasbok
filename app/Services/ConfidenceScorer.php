<?php
// FILE: app/Services/ConfidenceScorer.php
//
// Computes a confidence score (0-100) for an interchange group-vehicle
// pairing by summing the weight of every ACCEPTED evidence record, and
// suggests a verification status from that score. Matches the model
// demonstrated in AutoZenith_Basic_Interchange_Model.xlsx:
//   OEM Number Match (35) + Transmission Code Match (25) = 65 -> Verified
//
// IMPORTANT: this only SUGGESTS a status. Staff can (and per the
// workbook's own example data, sometimes should) manually override —
// two pairings scored identically at 45 points were manually set to
// different statuses (Probable vs Under Review) based on judgment the
// score alone can't capture. Never auto-write verification_status
// without staff confirmation for anything below the Verified threshold.

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ConfidenceScorer
{
    // Thresholds — adjust as real evidence patterns accumulate. These
    // are starting points inferred from the workbook's example scores
    // (65/70 -> Verified, 40-55 -> Probable/Under Review split).
    public const VERIFIED_THRESHOLD = 60;
    public const PROBABLE_THRESHOLD = 40;

    /**
     * Sum the weight of all ACCEPTED evidence for a given interchange
     * group, capped at 100.
     */
    public static function scoreForGroup(int $groupId): int
    {
        $total = (int) DB::table('interchange_evidence')
            ->where('group_id', $groupId)
            ->where('status', 'Accepted')
            ->sum('weight');

        return min(100, $total);
    }

    /**
     * Count of ACCEPTED evidence records — the workbook's "Source Count"
     * column, shown alongside the score so staff can see not just HOW
     * confident, but how many independent things back that confidence.
     */
    public static function sourceCountForGroup(int $groupId): int
    {
        return DB::table('interchange_evidence')
            ->where('group_id', $groupId)
            ->where('status', 'Accepted')
            ->count();
    }

    /**
     * Suggest a verification status from a score. This is a SUGGESTION
     * only — see class-level note. Never treat this as authoritative
     * for anything below VERIFIED_THRESHOLD.
     */
    public static function suggestStatus(int $score): string
    {
        if ($score >= self::VERIFIED_THRESHOLD) return 'Verified';
        if ($score >= self::PROBABLE_THRESHOLD) return 'Probable';
        return 'Under Review';
    }

    /**
     * Recompute and persist the score + source count for a specific
     * group-vehicle pairing row in part_interchange_vehicles. Does NOT
     * overwrite verification_status if staff already set one manually
     * — only fills it in if it's still at the default 'Under Review'
     * with zero evidence, to avoid silently reverting a human decision.
     *
     * Call this after adding/editing/accepting an evidence record.
     */
    public static function recomputeForPairing(int $groupId, int $pairingId): void
    {
        $score       = self::scoreForGroup($groupId);
        $sourceCount = self::sourceCountForGroup($groupId);

        $pairing = DB::table('part_interchange_vehicles')->where('id', $pairingId)->first();
        if (!$pairing) return;

        $update = [
            'confidence_score' => $score,
            'source_count'     => $sourceCount,
            'updated_at'       => now(),
        ];

        // Only auto-set status if staff haven't already made a manual
        // call on this pairing (i.e. it's still sitting at the
        // untouched default with no evidence recorded yet).
        $untouched = ($pairing->verification_status === 'Under Review' && (int) $pairing->source_count === 0);
        if ($untouched) {
            $update['verification_status'] = self::suggestStatus($score);
        }

        DB::table('part_interchange_vehicles')->where('id', $pairingId)->update($update);
    }
}
