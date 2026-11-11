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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use App\Services\ConsumableBatches;

class StockTransferController extends Controller
{
    const LOCATIONS = [
        'Waxahachie TX', 'Kennedale TX', 'Elkhorn WI',
        'Ile-Ife Nigeria', 'Ibadan Nigeria', 'Lagos Nigeria', 'Abuja Nigeria', 'Akure Nigeria', 'Accra Ghana',
    ];

    /** Bulk stock (consumables, electronics, computers, other, new car parts): a transfer moves a chosen QUANTITY. */
    private function isBulk(object $part): bool
    {
        return in_array($part->part_category, ConsumableController::stockableCategories(), true);
    }

    /** Same product at the same location = one stock total, even if it was entered as several rows over time. */
    private function productKey(object $p): string
    {
        return mb_strtolower(trim((string) $p->part_category)) . '|' . mb_strtolower(trim((string) $p->part_name)) . '|'
            . mb_strtolower(trim((string) ($p->brand ?? ''))) . '|' . str_replace(' ', '', mb_strtolower(trim((string) ($p->unit_size ?? ''))));
    }

    /** Every Available row of this product at this location (oldest first), locked for the update. */
    private function siblingRows(object $part, string $location)
    {
        $q = DB::table('parts_inventory')
            ->where('location', $location)
            ->where('status', 'Available')
            ->where('stock_qty', '>', 0)
            ->where('part_category', $part->part_category)
            ->whereRaw('LOWER(TRIM(part_name)) = ?', [mb_strtolower(trim((string) $part->part_name))])
            ->whereRaw("LOWER(TRIM(COALESCE(brand,''))) = ?", [mb_strtolower(trim((string) ($part->brand ?? '')))])
            ->whereRaw("REPLACE(LOWER(TRIM(COALESCE(unit_size,''))),' ','') = ?", [str_replace(' ', '', mb_strtolower(trim((string) ($part->unit_size ?? ''))))]);
        if (Schema::hasColumn('parts_inventory', 'deleted_at')) $q->whereNull('deleted_at');
        return $q->orderBy('id')->lockForUpdate()->get();
    }

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

        $parts = $query->select('id', 'part_code', 'part_name', 'brand', 'model', 'condition_grade', 'location', 'part_category', 'unit_size', 'stock_qty')
            ->orderBy('part_name')->orderBy('id')->limit(300)->get()
            ->map(function ($p) {
                $p->is_bulk   = $this->isBulk($p);
                $p->stock_qty = (int) $p->stock_qty;
                return $p;
            })
            ->filter(fn ($p) => !$p->is_bulk || $p->stock_qty > 0)->values();

        // Bulk stock: one line per product with the combined quantity (rows of the same product are added up).
        $merged = [];
        foreach ($parts as $p) {
            if (!$p->is_bulk) { $merged[] = $p; continue; }
            $k = 'b|' . $this->productKey($p);
            if (isset($merged[$k])) { $merged[$k]->stock_qty += $p->stock_qty; continue; }
            $merged[$k] = $p;                       // first (oldest) row represents the product
        }
        $parts = collect(array_values($merged))->take(50)->values();

        return response()->json(['parts' => $parts]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_location' => 'required|string',
            'to_location'   => 'required|string|different:from_location',
            'part_ids'      => 'required|array|min:1',
            'part_ids.*'    => 'required|exists:parts_inventory,id',
            'qty'           => 'nullable|array',
            'qty.*'         => 'nullable|integer|min:1',
        ]);

        $year = date('Y');
        $seq  = DB::table('stock_transfers')->whereYear('created_at', $year)->count() + 1;
        $transferNo = "TRF-{$year}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            // Validate every line against the live stock, locked, before anything is changed.
            $errors = [];
            $lines  = [];
            foreach ($request->part_ids as $partId) {
                $part = DB::table('parts_inventory')->where('id', $partId)->lockForUpdate()->first();
                if (!$part) { $errors[] = "A selected part no longer exists."; continue; }
                if ($part->status !== 'Available') { $errors[] = "{$part->part_code} is no longer Available."; continue; }
                if ($part->location !== $request->from_location) { $errors[] = "{$part->part_code} is not at {$request->from_location}."; continue; }

                $qty = 1;
                $rows = collect([$part]);
                if ($this->isBulk($part)) {
                    $qty = (int) ($request->input("qty.{$partId}") ?: 1);
                    if ($qty < 1) { $errors[] = "{$part->part_name}: enter a quantity of at least 1."; continue; }
                    $rows  = $this->siblingRows($part, $request->from_location);
                    $total = (int) $rows->sum('stock_qty');
                    if ($rows->isEmpty() || $qty > $total) {
                        $errors[] = "{$part->part_name}: only {$total} available at {$request->from_location}, you asked for {$qty}.";
                        continue;
                    }
                }
                $lines[] = [$part, $qty, $rows];
            }
            if (!empty($errors)) {
                DB::rollBack();
                return back()->withInput()->with('error', implode(' | ', $errors));
            }

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

            foreach ($lines as [$part, $qty, $rows]) {
                $bulk = $this->isBulk($part);
                DB::table('stock_transfer_items')->insert([
                    'transfer_id'     => $transferId,
                    'part_id'         => $part->id,
                    'part_name'       => $part->part_name,
                    'part_code'       => $part->part_code,
                    'qty'             => $qty,
                    'is_bulk'         => $bulk ? 1 : 0,
                    'part_category'   => $part->part_category,
                    'unit_size'       => $part->unit_size ?? null,
                    'brand'           => $part->brand,
                    'model'           => $part->model,
                    'condition_grade' => $part->condition_grade,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                if ($bulk) {
                    // Only the chosen units leave (taken from the oldest row first). The rest stays here, still Available and sellable.
                    $remaining = $qty;
                    foreach ($rows as $row) {
                        if ($remaining <= 0) break;
                        $take = min($remaining, (int) $row->stock_qty);
                        $left = (int) $row->stock_qty - $take;
                        DB::table('parts_inventory')->where('id', $row->id)->update([
                            'stock_qty'  => $left,
                            'status'     => $left > 0 ? 'Available' : 'Out of Stock',
                            'updated_at' => now(),
                        ]);
                        ConsumableBatches::record($row, -$take, $left, 'transfer_out', null, "Sent to {$request->to_location} on {$transferNo}");
                        $remaining -= $take;
                    }
                } else {
                    // A used part is a single item: it leaves the sellable pool while in transit.
                    DB::table('parts_inventory')->where('id', $part->id)->update([
                        'status'           => 'In Transit',
                        'storage_shelf_id' => null,
                        'bin_location'     => null,
                        'updated_at'       => now(),
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Transfer could not be created: ' . $e->getMessage());
        }

        return redirect()->route('admin.transfers.show', $transferId)
            ->with('success', "Transfer {$transferNo} created - {$request->from_location} to {$request->to_location}.");
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

        // Destination bin: required for every used part (one bin per part). For bulk items the bin is optional -
        // room-level storage is allowed, and a product already shelved at the destination keeps its shelf.
        $destBins = $request->input('dest_bins', []);
        $missing = [];
        foreach ($items as $item) {
            if (empty($item->is_bulk) && empty($destBins[$item->id])) {
                $missing[] = $item->part_name;
            }
        }
        if (!empty($missing)) {
            return back()->with('error', 'Select a destination bin for every used part before accepting - missing for: ' . implode(', ', $missing));
        }

        $binCodesById = DB::table('storage_shelves')->whereIn('id', array_filter(array_values($destBins)))->pluck('full_bin_code', 'id');
        $hasDeleted   = Schema::hasColumn('parts_inventory', 'deleted_at');
        $notes        = [];
        $units        = 0;

        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                $shelfId = $destBins[$item->id] ?? null;

                if (empty($item->is_bulk)) {
                    DB::table('parts_inventory')->where('id', $item->part_id)->update([
                        'status'           => 'Available',
                        'location'         => $transfer->to_location,
                        'storage_shelf_id' => $shelfId,
                        'bin_location'     => $binCodesById[$shelfId] ?? null,
                        'updated_at'       => now(),
                    ]);
                    $units++;
                    continue;
                }

                // Bulk item: add the transferred units to the product at the destination (create it if it is not
                // stocked there yet), under the same product number.
                $src = DB::table('parts_inventory')->where('id', $item->part_id)->first();
                if (!$src) throw new \RuntimeException("{$item->part_name} no longer exists.");

                $existing = DB::table('parts_inventory')
                    ->when($hasDeleted, fn ($q) => $q->whereNull('deleted_at'))
                    ->where('location', $transfer->to_location)
                    ->where('part_category', $src->part_category)
                    ->whereRaw('LOWER(TRIM(part_name)) = ?', [mb_strtolower(trim($src->part_name))])
                    ->whereRaw("LOWER(TRIM(COALESCE(brand,''))) = ?", [mb_strtolower(trim((string) $src->brand))])
                    ->whereRaw("REPLACE(LOWER(TRIM(COALESCE(unit_size,''))),' ','') = ?", [str_replace(' ', '', mb_strtolower(trim((string) ($src->unit_size ?? ''))))])
                    ->orderBy('id')->lockForUpdate()->first();

                if ($existing) {
                    $newQty = (int) $existing->stock_qty + (int) $item->qty;
                    $upd = [
                        'stock_qty'  => $newQty,
                        'status'     => in_array($existing->status, ['Out of Stock', 'Sold'], true) ? 'Available' : $existing->status,
                        'updated_at' => now(),
                    ];
                    if ($shelfId && empty($existing->storage_shelf_id)) {
                        $upd['storage_shelf_id'] = $shelfId;
                        $upd['bin_location']     = $binCodesById[$shelfId] ?? null;
                    }
                    DB::table('parts_inventory')->where('id', $existing->id)->update($upd);
                    ConsumableBatches::record($existing, (int) $item->qty, $newQty, 'transfer_in', null, "Received on {$transfer->transfer_no} from {$transfer->from_location}");
                    $destId = $existing->id;
                } else {
                    $currency = InvoiceController::currencyForLocation($transfer->to_location);
                    $sameCurrency = ($src->currency_code ?? null) === $currency['code'];
                    $row = (array) $src;
                    foreach (['id', 'sold_at', 'deleted_at', 'source_ref'] as $drop) unset($row[$drop]);
                    foreach (array_keys($row) as $col) {
                        if (stripos($col, 'barcode') !== false) unset($row[$col]);
                    }
                    $row = array_merge($row, [
                        'location'         => $transfer->to_location,
                        'storage_shelf_id' => $shelfId ?: null,
                        'bin_location'     => $binCodesById[$shelfId] ?? null,
                        'stock_qty'        => (int) $item->qty,
                        'currency_code'    => $currency['code'],
                        'price_local'      => $sameCurrency ? $src->price_local : 0,
                        'price_usd'        => $sameCurrency ? $src->price_usd : 0,
                        'price_wholesale'  => $sameCurrency ? ($src->price_wholesale ?? null) : null,
                        // A price in another currency is not guessed: the line is held until a price is set.
                        'status'           => $sameCurrency ? 'Available' : 'Hold',
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                    $destId = DB::table('parts_inventory')->insertGetId($row);
                    $created = DB::table('parts_inventory')->where('id', $destId)->first();
                    ConsumableBatches::record($created, (int) $item->qty, (int) $item->qty, 'transfer_in', null, "Received on {$transfer->transfer_no} from {$transfer->from_location}");
                    if (!$sameCurrency) $notes[] = "{$src->part_name} is on Hold at {$transfer->to_location} until its price there is set.";
                }

                DB::table('stock_transfer_items')->where('id', $item->id)->update(['dest_part_id' => $destId, 'updated_at' => now()]);
                $units += (int) $item->qty;
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
            ->with('success', "Transfer received - {$units} unit(s) now at {$transfer->to_location}." . ($notes ? ' ' . implode(' ', $notes) : ''));
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
                if (!empty($item->is_bulk)) {
                    // Put the units that were taken back on the source stock.
                    $src = DB::table('parts_inventory')->where('id', $item->part_id)->lockForUpdate()->first();
                    if ($src) {
                        $back = (int) $src->stock_qty + (int) $item->qty;
                        DB::table('parts_inventory')->where('id', $src->id)->update([
                            'stock_qty'  => $back,
                            'status'     => in_array($src->status, ['Out of Stock', 'Sold'], true) ? 'Available' : $src->status,
                            'updated_at' => now(),
                        ]);
                        ConsumableBatches::record($src, (int) $item->qty, $back, 'transfer_cancel', null, "Transfer {$transfer->transfer_no} cancelled");
                    }
                    continue;
                }
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

        return redirect()->route('admin.transfers.index')->with('success', 'Transfer cancelled - parts and units restored at the origin.');
    }
}
