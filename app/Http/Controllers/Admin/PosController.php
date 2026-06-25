<?php
// FILE: app/Http/Controllers/Admin/PosController.php
//
// Phase D — supermarket-style POS. Cashier scans parts (barcode = the
// existing part_code, no new field needed), system builds a cart,
// cashier completes the sale. This creates a real `invoices` row
// (same model as Manual Invoice) and deducts stock_qty exactly like
// Manual Invoice does — same anti-oversell protection, same
// transaction safety.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PosController extends Controller
{
    const LOCATIONS = [
        'Waxahachie TX', 'Kennedale TX', 'Elkhorn WI',
        'Ile-Ife Nigeria', 'Ibadan Nigeria', 'Lagos Nigeria', 'Abuja Nigeria', 'Akure Nigeria', 'Accra Ghana',
    ];

    public function index()
    {
        return view('admin.pos.index', ['locations' => self::LOCATIONS]);
    }

    // =========================================================
    // AJAX: GET /admin/pos/lookup?code=...&location=...
    // The scanner gun types the barcode (= part_code) into a focused
    // text field and presses Enter automatically — this endpoint is
    // what that Enter keypress calls. Exact-match only, since
    // part_code is unique and that's the whole point of a barcode.
    // =========================================================
    public function lookup(Request $request)
    {
        $code = trim($request->get('code', ''));
        $loc  = $request->get('location', '');

        if ($code === '') {
            return response()->json(['error' => 'No code scanned.'], 422);
        }

        $part = DB::table('parts_inventory')
            ->where('part_code', $code)
            ->where('location', $loc)
            ->first();

        if (!$part) {
            // Try without location filter, to give a clearer error
            // (wrong location vs genuinely doesn't exist).
            $anywhere = DB::table('parts_inventory')->where('part_code', $code)->first();
            if ($anywhere) {
                return response()->json(['error' => "{$code} exists but is at {$anywhere->location}, not {$loc}."], 404);
            }
            return response()->json(['error' => "No part found with code {$code}."], 404);
        }

        if ($part->status !== 'Available') {
            return response()->json(['error' => "{$code} ({$part->part_name}) is not Available (status: {$part->status})."], 422);
        }

        if ($part->stock_qty < 1) {
            return response()->json(['error' => "{$code} ({$part->part_name}) is out of stock."], 422);
        }

        return response()->json(['part' => [
            'id'            => $part->id,
            'part_code'     => $part->part_code,
            'part_name'     => $part->part_name,
            'brand'         => $part->brand,
            'model'         => $part->model,
            'condition_grade'=> $part->condition_grade,
            'price_local'   => $part->price_local ?? $part->price_usd,
            'currency_code' => $part->currency_code ?? 'USD',
            'stock_qty'     => $part->stock_qty,
        ]]);
    }

    // =========================================================
    // POST /admin/pos/checkout — complete the sale.
    // Same stock-enforcement and transaction-safety pattern as
    // InvoiceController::storeManual().
    // =========================================================
    public function checkout(Request $request)
    {
        $request->validate([
            'location'        => 'required|string',
            'payment_method'  => 'required|string',
            'items'           => 'required|array|min:1',
            'items.*.part_id' => 'required|exists:parts_inventory,id',
            'items.*.qty'     => 'required|integer|min:1',
        ]);

        $location = $request->location;

        // ── STOCK ENFORCEMENT (re-checked authoritatively here) ──────
        $stockErrors = [];
        $parts = [];
        foreach ($request->items as $item) {
            $part = DB::table('parts_inventory')->where('id', $item['part_id'])->first();
            $qty  = (int) $item['qty'];

            if (!$part) { $stockErrors[] = "A scanned part no longer exists."; continue; }
            if ($part->status !== 'Available') { $stockErrors[] = "{$part->part_code} is no longer Available."; continue; }
            if ($qty > $part->stock_qty) { $stockErrors[] = "{$part->part_code}: only {$part->stock_qty} in stock."; continue; }
            $parts[] = ['part' => $part, 'qty' => $qty];
        }

        if (!empty($stockErrors)) {
            return response()->json(['success' => false, 'error' => implode(' | ', $stockErrors)], 422);
        }

        $currencyCode = $parts[0]['part']->currency_code ?? 'USD';
        $createdAt    = now();
        $invoiceNo    = 'POS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        DB::beginTransaction();
        try {
            $subtotalLocal = 0;
            $lineItemsForReceipt = [];

            $invoiceId = DB::table('invoices')->insertGetId([
                'invoice_no'        => $invoiceNo,
                'invoice_type'      => 'parts',
                'customer_name'     => $request->customer_name ?: 'Walk-in Customer',
                'customer_phone'    => $request->customer_phone ?: null,
                'location'          => $location,
                'currency_code'     => $currencyCode,
                'subtotal_local'    => 0, // updated below once computed
                'subtotal_usd'      => 0,
                'payment_method'    => $request->payment_method,
                'created_by'        => Session::get('staff_name') ?? 'Cashier',
                'notes'             => 'POS sale',
                'created_at'        => $createdAt,
                'updated_at'        => $createdAt,
            ]);

            foreach ($parts as $p) {
                $part = $p['part'];
                $qty  = $p['qty'];
                $priceLocal = $part->price_local ?? $part->price_usd;
                $lineLocal  = $priceLocal * $qty;
                $subtotalLocal += $lineLocal;

                DB::table('invoice_items')->insert([
                    'invoice_id'       => $invoiceId,
                    'part_id'          => $part->id,
                    'part_name'        => $part->part_name,
                    'part_code'        => $part->part_code,
                    'brand'            => $part->brand,
                    'model'            => $part->model,
                    'condition_grade'  => $part->condition_grade,
                    'qty'              => $qty,
                    'unit_price_local' => $priceLocal,
                    'unit_price_usd'   => $priceLocal,
                    'created_at'       => $createdAt,
                    'updated_at'       => $createdAt,
                ]);

                // Re-check + lock inside the transaction (race-condition
                // safe, same as Manual Invoice).
                $locked = DB::table('parts_inventory')->where('id', $part->id)->lockForUpdate()->first();
                if (!$locked || $locked->stock_qty < $qty) {
                    throw new \Exception("Stock for {$part->part_code} changed before checkout completed.");
                }
                $newQty = $locked->stock_qty - $qty;
                DB::table('parts_inventory')->where('id', $part->id)->update([
                    'stock_qty'  => $newQty,
                    'status'     => $newQty <= 0 ? 'Sold' : 'Available',
                    'updated_at' => now(),
                ]);
            }

            DB::table('invoices')->where('id', $invoiceId)->update([
                'subtotal_local' => $subtotalLocal,
                'subtotal_usd'   => $subtotalLocal,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Checkout failed: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'success'    => true,
            'invoice_id' => $invoiceId,
            'invoice_no' => $invoiceNo,
            'print_url'  => route('admin.invoices.show.manual', $invoiceId),
        ]);
    }
}
