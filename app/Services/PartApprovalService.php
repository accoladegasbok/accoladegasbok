<?php
// FILE: app/Services/PartApprovalService.php
//
// Manager approval before new stock goes live.
//  - Manager and admin can approve. Supervisors and staff cannot.
//  - A part created by a manager or admin goes live straight away.
//  - Anyone else's new part is saved as "Under Review": the website, search, carts and invoices only use
//    "Available" parts, so an Under Review part is invisible to customers until it is approved.
//  - A consumable restock (adding units to stock that already exists) waits for approval the same way.

namespace App\Services;

use App\Support\StaffRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PartApprovalService
{
    public const UNDER_REVIEW = 'Under Review';
    public const APPROVERS    = ['admin', 'manager'];

    public static function canApprove(): bool
    {
        return in_array(StaffRole::current(), self::APPROVERS, true);
    }

    /** Status a brand-new part should be saved with. */
    public static function initialStatus(): string
    {
        return self::canApprove() ? 'Available' : self::UNDER_REVIEW;
    }

    /** Call right after a new part row is inserted: files it for review if it needs one. */
    public static function submitNew(int $partId): bool
    {
        $status = DB::table('parts_inventory')->where('id', $partId)->value('status');
        if ($status !== self::UNDER_REVIEW) return false;

        DB::table('part_approvals')->insert([
            'part_id'               => $partId,
            'type'                  => 'new_part',
            'status'                => 'pending',
            'requested_by_staff_id' => Session::get('staff_id'),
            'requested_by_name'     => Session::get('staff_name'),
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
        return true;
    }

    /** Units staff want to add to existing stock; they are only added once a manager approves. */
    public static function requestRestock(int $partId, int $qty): void
    {
        DB::table('part_approvals')->insert([
            'part_id'               => $partId,
            'type'                  => 'restock',
            'qty_to_add'            => max(1, $qty),
            'status'                => 'pending',
            'requested_by_staff_id' => Session::get('staff_id'),
            'requested_by_name'     => Session::get('staff_name'),
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
    }

    public static function pendingCount(): int
    {
        try {
            return (int) DB::table('part_approvals')->where('status', 'pending')->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Approve one request. Returns a short message for the screen. */
    public static function approve(int $approvalId): string
    {
        abort_unless(self::canApprove(), 403, 'Only a manager or admin can approve inventory.');

        return DB::transaction(function () use ($approvalId) {
            $a = DB::table('part_approvals')->where('id', $approvalId)->lockForUpdate()->first();
            if (!$a || $a->status !== 'pending') return 'Already handled.';

            $part = DB::table('parts_inventory')->where('id', $a->part_id)->lockForUpdate()->first();
            if (!$part) {
                self::close($approvalId, 'rejected', 'Part no longer exists');
                return 'Part no longer exists.';
            }

            if ($a->type === 'restock') {
                $newQty = (int) ($part->stock_qty ?? 0) + (int) $a->qty_to_add;
                DB::table('parts_inventory')->where('id', $part->id)->update([
                    'stock_qty'  => $newQty,
                    'status'     => in_array($part->status, ['Sold', 'Out of Stock'], true) ? 'Available' : $part->status,
                    'updated_at' => now(),
                ]);
                self::close($approvalId, 'approved');
                return "{$part->part_code}: +{$a->qty_to_add} approved, now {$newQty} in stock.";
            }

            if ($part->status === self::UNDER_REVIEW) {
                DB::table('parts_inventory')->where('id', $part->id)->update(['status' => 'Available', 'updated_at' => now()]);
            }
            self::close($approvalId, 'approved');
            return "{$part->part_code} approved and live.";
        });
    }

    public static function reject(int $approvalId, string $reason): string
    {
        abort_unless(self::canApprove(), 403, 'Only a manager or admin can approve inventory.');

        $a = DB::table('part_approvals')->where('id', $approvalId)->first();
        if (!$a || $a->status !== 'pending') return 'Already handled.';

        // A rejected new part stays off the website (put on Hold) so it can be corrected or removed.
        if ($a->type === 'new_part') {
            DB::table('parts_inventory')->where('id', $a->part_id)->where('status', self::UNDER_REVIEW)
                ->update(['status' => 'Hold', 'updated_at' => now()]);
        }
        self::close($approvalId, 'rejected', $reason);
        return 'Rejected.';
    }

    /** Called when a manager/admin makes an Under Review part live by editing it directly. */
    public static function closeNewPart(int $partId): void
    {
        DB::table('part_approvals')->where('part_id', $partId)->where('type', 'new_part')->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'reviewed_by_staff_id' => Session::get('staff_id'),
                'reviewed_by_name'     => Session::get('staff_name'),
                'reviewed_at'          => now(),
                'updated_at'           => now(),
            ]);
    }

    private static function close(int $approvalId, string $status, ?string $note = null): void
    {
        DB::table('part_approvals')->where('id', $approvalId)->update([
            'status'               => $status,
            'note'                 => $note ? mb_substr($note, 0, 255) : null,
            'reviewed_by_staff_id' => Session::get('staff_id'),
            'reviewed_by_name'     => Session::get('staff_name'),
            'reviewed_at'          => now(),
            'updated_at'           => now(),
        ]);
    }
}
