<?php
// FILE: app/Http/Controllers/Admin/StockTransferController.php
// Phase C2 — move parts between locations with a proper paper trail.
// Waybills are intentionally price-free (driver/customs-facing
// document); the admin transfer detail page can show value for
// internal accounting if ever needed, but the printable waybill never
// does.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class StockTransferController extends Controller
{
    const LOCATIONS = [
        'Waxahachie TX', 'Kennedale TX', 'Elkhorn WI',
        'Ile-Ife Nigeria', 'Ibadan Nigeria', 'Lagos Nigeria', 'Abuja Nigeria', 'Akure Nigeria', 'Accra Ghana',
    ];

    public function index(Request $request)
    {
        $query = DB::table('stock_transfers')->orderByDesc('created_at');
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        $transfers = $query->paginate(25)->withQueryString();

        $counts = DB::table('stock_transfers')
            ->select('status', DB::raw('count(*) as n'))
            ->groupBy('status')->pluck('n', 'status');

        return view('admin.transfers.index', compact('transfers', 'counts'));
    }

    public function create()
    {
        return view('admin.transfers.create', ['locations' => self::LOCATIONS]);
    }

    // AJAX: parts available at the chosen from-location
    public function searchParts(Request $request)
    {
        $loc = $request->get('location', '');
        $q   = trim($request->get('q', ''));

        $query = DB::table('parts_inventory')
            ->where('status', 'Available')
            ->where('location', $loc);

        if ($q !== '') {
            $query->where(function ($sq) use ($q) {
                $sq->where('part_name', 'like', "%{$q}%")
                   ->orWhere('part_code', 'like', "%{$q}%")
                   ->orWhere('brand', 'like', "%{$q}%")
                   ->orWhere('model', 'like', "%{$q}%");
            });
        }

        $parts = $query->select('id', 'part_code', 'part_name', 'brand', 'model', 'condition_grade', 'location')
            ->orderBy('part_name')->limit(50)->get();

        return response()->json(['parts' => $parts]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_location' => 'required|string',
            'to_location'   => 'required|string|different:from_location',
            'part_ids'      => 'required|array|min:1',
            'part_ids.*'    => 'required|exists:parts_inventory,id',
        ]);

        $errors = [];
        $parts = [];
        foreach ($request->part_ids as $partId) {
            $part = DB::table('parts_inventory')->where('id', $partId)->first();
            if (!$part) { $errors[] = "A selected part no longer exists."; continue; }
            if ($part->status !== 'Available') { $errors[] = "{$part->part_code} is no longer Available."; continue; }
            if ($part->location !== $request->from_location) { $errors[] = "{$part->part_code} is not at {$request->from_location}."; continue; }
            $parts[] = $part;
        }

        if (!empty($errors)) {
            return back()->withInput()->with('error', implode(' | ', $errors));
        }

        $year = date('Y');
        $seq  = DB::table('stock_transfers')->whereYear('created_at', $year)->count() + 1;
        $transferNo = "TRF-{$year}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            $transferId = DB::table('stock_transfers')->insertGetId([
                'transfer_no'         => $transferNo,
                'from_location'       => $request->from_location,
                'to_location'         => $request->to_location,
                'status'              => 'in_transit',
                'created_by_staff_id' => Session::get('staff_id'),
                'notes'               => $request->notes,
                'shipped_at'          => now(),
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            foreach ($parts as $part) {
                DB::table('stock_transfer_items')->insert([
                    'transfer_id'     => $transferId,
                    'part_id'         => $part->id,
                    'part_name'       => $part->part_name,
                    'part_code'       => $part->part_code,
                    'brand'           => $part->brand,
                    'model'           => $part->model,
                    'condition_grade' => $part->condition_grade,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                // Part leaves the "sellable at this location" pool while
                // in transit — can't be sold from either location
                // until it's marked Received at the destination.
                DB::table('parts_inventory')->where('id', $part->id)->update([
                    'status'           => 'In Transit',
                    'storage_shelf_id' => null,
                    'bin_location'     => null,
                    'updated_at'       => now(),
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Transfer could not be created: ' . $e->getMessage());
        }

        return redirect()->route('admin.transfers.show', $transferId)
            ->with('success', "Transfer {$transferNo} created — {$request->from_location} → {$request->to_location}.");
    }

    public function show(int $id)
    {
        $transfer = DB::table('stock_transfers')->where('id', $id)->first();
        abort_if(!$transfer, 404);

        $items = DB::table('stock_transfer_items')->where('transfer_id', $id)->get();

        $createdBy  = $transfer->created_by_staff_id ? DB::table('staff')->where('id', $transfer->created_by_staff_id)->value('name') : null;
        $receivedBy = $transfer->received_by_staff_id ? DB::table('staff')->where('id', $transfer->received_by_staff_id)->value('name') : null;

        // ── #12 — full room address shown for both origin and
        // destination, so the receiving agent can positively confirm
        // this is the right place before accepting.
        $fromRooms = DB::table('storage_rooms')->where('location', $transfer->from_location)->get();
        $toRooms   = DB::table('storage_rooms')->where('location', $transfer->to_location)->get();

        // Destination bins available for picking, if not yet received
        // — one bin per item, so occupied bins are excluded here too.
        $toBins = collect();
        if ($transfer->status === 'in_transit') {
            $occupiedBinIds = DB::table('parts_inventory')
                ->whereIn('status', ['Available', 'Reserved', 'Hold'])
                ->whereNotNull('storage_shelf_id')
                ->pluck('storage_shelf_id');

            $toBins = DB::table('storage_shelves as s')
                ->join('storage_rooms as r', 'r.id', '=', 's.storage_room_id')
                ->where('r.location', $transfer->to_location)
                ->whereNotIn('s.id', $occupiedBinIds)
                ->select('s.id', 's.full_bin_code', 'r.name as room_name')
                ->orderBy('r.name')->orderBy('s.full_bin_code')->get();
        }

        return view('admin.transfers.show', compact('transfer', 'items', 'createdBy', 'receivedBy', 'fromRooms', 'toRooms', 'toBins'));
    }

    // Printable, price-free waybill
    public function waybill(int $id)
    {
        $transfer = DB::table('stock_transfers')->where('id', $id)->first();
        abort_if(!$transfer, 404);
        $items = DB::table('stock_transfer_items')->where('transfer_id', $id)->get();

        $fromRooms = DB::table('storage_rooms')->where('location', $transfer->from_location)->get();
        $toRooms   = DB::table('storage_rooms')->where('location', $transfer->to_location)->get();

        // Full company letterhead info — same source invoices already
        // use, so the waybill matches the rest of the document set
        // rather than showing just a bare location name and a QR code.
        $fromBusinessInfo = app(\App\Http\Controllers\Admin\InvoiceController::class)->getBusinessInfo($transfer->from_location);
        $toBusinessInfo   = app(\App\Http\Controllers\Admin\InvoiceController::class)->getBusinessInfo($transfer->to_location);

        $fromAddress = $fromRooms->pluck('address')->filter()->first() ?? null;
        $toAddress   = $toRooms->pluck('address')->filter()->first() ?? null;

        return view('admin.transfers.waybill', compact('transfer', 'items', 'fromRooms', 'toRooms', 'fromBusinessInfo', 'toBusinessInfo', 'fromAddress', 'toAddress'));
    }

    public function receive(Request $request, int $id)
    {
        $transfer = DB::table('stock_transfers')->where('id', $id)->first();
        abort_if(!$transfer, 404);

        if ($transfer->status === 'received') {
            return back()->with('error', 'This transfer was already marked received.');
        }

        $items = DB::table('stock_transfer_items')->where('transfer_id', $id)->get();

        // ── #12 — destination bin is now REQUIRED per item to accept
        // a transfer, not optional. Staff must positively place every
        // incoming part into a real bin at the destination before the
        // transfer can be marked received.
        $destBins = $request->input('dest_bins', []);
        $missing = [];
        foreach ($items as $item) {
            if (empty($destBins[$item->id])) {
                $missing[] = $item->part_name;
            }
        }
        if (!empty($missing)) {
            return back()->with('error', 'Select a destination bin for every item before accepting — missing for: ' . implode(', ', $missing));
        }

        $binCodesById = DB::table('storage_shelves')->whereIn('id', array_values($destBins))->pluck('full_bin_code', 'id');

        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                $shelfId = $destBins[$item->id];
                DB::table('parts_inventory')->where('id', $item->part_id)->update([
                    'status'           => 'Available',
                    'location'         => $transfer->to_location,
                    'storage_shelf_id' => $shelfId,
                    'bin_location'     => $binCodesById[$shelfId] ?? null,
                    'updated_at'       => now(),
                ]);
            }

            DB::table('stock_transfers')->where('id', $id)->update([
                'status'               => 'received',
                'received_by_staff_id' => Session::get('staff_id'),
                'received_at'          => now(),
                'updated_at'           => now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Could not mark as received: ' . $e->getMessage());
        }

        return redirect()->route('admin.transfers.show', $id)
            ->with('success', "Transfer received — {$items->count()} part(s) now Available at {$transfer->to_location}. Don't forget to assign bin locations.");
    }

    public function cancel(int $id)
    {
        $transfer = DB::table('stock_transfers')->where('id', $id)->first();
        abort_if(!$transfer, 404);

        if ($transfer->status === 'received') {
            return back()->with('error', 'Cannot cancel a transfer that has already been received.');
        }

        $items = DB::table('stock_transfer_items')->where('transfer_id', $id)->get();

        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                DB::table('parts_inventory')->where('id', $item->part_id)->update([
                    'status'     => 'Available',
                    'updated_at' => now(),
                ]);
            }
            DB::table('stock_transfers')->where('id', $id)->update(['status' => 'cancelled', 'updated_at' => now()]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Could not cancel: ' . $e->getMessage());
        }

        return redirect()->route('admin.transfers.index')->with('success', 'Transfer cancelled — parts restored to Available at origin.');
    }
}
