<?php
// FILE: app/Http/Controllers/Admin/AdminOrderController.php
//
// Lets staff place an order on behalf of a walk-in or phone customer
// using a cart-style UI (live stock checking, same part search as
// elsewhere) — but unlike the Manual Invoice tool, this creates a real
// row in `orders` (going through the same confirm-payment/status
// pipeline as online orders), not an `invoices` row. This is the
// safer, stock-checked alternative; Manual Invoice remains available
// for quick/simple sales.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class AdminOrderController extends Controller
{
    const LOCATIONS = [
        'Waxahachie TX', 'Kennedale TX', 'Elkhorn WI',
        'Ile-Ife Nigeria', 'Ibadan Nigeria', 'Oshodi Lagos', 'Accra Ghana',
    ];

    private function liveRates(): array
    {
        return Cache::remember('exchange_rates', now()->addHours(24), function () {
            try {
                $r = Http::timeout(5)->get('https://open.er-api.com/v6/latest/USD')->json('rates', []);
                return ['NGN' => round($r['NGN'] ?? 1600, 2), 'GHS' => round($r['GHS'] ?? 15.5, 2)];
            } catch (\Exception $e) {
                return ['NGN' => 1600, 'GHS' => 15.5];
            }
        });
    }

    // =========================================================
    // GET /admin/orders/place/create — the cart-style staff order form
    // =========================================================
    public function create()
    {
        return view('admin.orders.place', ['locations' => self::LOCATIONS]);
    }

    // =========================================================
    // AJAX: GET /admin/orders/place/search-parts?q=&location=
    // Only ever returns parts that are genuinely Available with
    // stock — staff cannot add anything that isn't really in stock.
    // =========================================================
    public function searchParts(Request $request)
    {
        $q   = trim($request->get('q', ''));
        $loc = $request->get('location', '');

        $query = DB::table('parts_inventory')
            ->where('status', 'Available')
            ->where('stock_qty', '>', 0);

        if ($loc) $query->where('location', $loc);

        if ($q !== '') {
            $query->where(function ($sq) use ($q) {
                $sq->where('part_name', 'like', "%{$q}%")
                   ->orWhere('part_code', 'like', "%{$q}%")
                   ->orWhere('brand', 'like', "%{$q}%")
                   ->orWhere('model', 'like', "%{$q}%");
            });
        }

        $parts = $query->select('id', 'part_code', 'part_name', 'brand', 'model', 'year_from', 'year_to',
                                  'condition_grade', 'location', 'price_local', 'currency_code', 'price_usd', 'stock_qty')
            ->orderBy('part_name')
            ->limit(50)
            ->get();

        return response()->json(['parts' => $parts]);
    }

    // =========================================================
    // POST /admin/orders/place — create the real order
    // =========================================================
    public function store(Request $request)
    {
        $request->validate([
            'customer_name'    => 'required|string|max:120',
            'customer_phone'   => 'required|string|max:30',
            'fulfillment_type' => 'required|in:Collection,Delivery',
            'payment_method'   => 'required|string',
            'payment_received' => 'nullable|boolean',
            'items'            => 'required|array|min:1',
            'items.*.part_id'  => 'required|exists:parts_inventory,id',
            'items.*.qty'      => 'required|integer|min:1',
        ]);

        // ── STOCK ENFORCEMENT — same principle as the manual invoice
        // tool: never reserve more than what's genuinely available,
        // re-checked authoritatively here server-side.
        $stockErrors = [];
        $parts = [];
        foreach ($request->items as $item) {
            $part = DB::table('parts_inventory')->where('id', $item['part_id'])->first();
            $qty  = (int) $item['qty'];

            if (!$part) { $stockErrors[] = "A selected part no longer exists."; continue; }
            if ($part->status !== 'Available') {
                $stockErrors[] = "{$part->part_code} ({$part->part_name}) is no longer Available.";
                continue;
            }
            if ($qty > $part->stock_qty) {
                $stockErrors[] = "{$part->part_code}: requested {$qty}, only {$part->stock_qty} in stock.";
                continue;
            }
            $parts[] = ['part' => $part, 'qty' => $qty];
        }

        if (!empty($stockErrors)) {
            return back()->withInput()->with('error', 'Cannot place this order — ' . implode(' | ', $stockErrors));
        }

        // ── Totals — each part's price_local is already fixed/correct
        // for its own location. Sum in whatever currency the parts are
        // actually in; convert to NGN/USD display fields using a live
        // rate ONLY for the secondary informational figure, matching
        // how the orders table has always stored both currencies.
        $rates = $this->liveRates();
        $totalUsd = 0;
        $totalNgn = 0;

        foreach ($parts as $p) {
            $priceLocal = $p['part']->price_local ?? $p['part']->price_usd;
            $currencyCode = $p['part']->currency_code ?? 'USD';
            $lineLocal = $priceLocal * $p['qty'];

            if ($currencyCode === 'NGN') {
                $totalNgn += $lineLocal;
                $totalUsd += $lineLocal / $rates['NGN'];
            } elseif ($currencyCode === 'GHS') {
                $totalUsd += $lineLocal / $rates['GHS'];
                $totalNgn += ($lineLocal / $rates['GHS']) * $rates['NGN'];
            } else { // USD
                $totalUsd += $lineLocal;
                $totalNgn += $lineLocal * $rates['NGN'];
            }
        }

        $year = date('Y');
        $seq  = DB::table('orders')->whereYear('created_at', $year)->count() + 1;
        $orderRef = "AZ-{$year}-" . str_pad($seq, 5, '0', STR_PAD_LEFT);

        $paymentReceived = $request->boolean('payment_received');

        // ── customer_country is a required column on `orders` (used
        // by the online checkout flow for shipping). For walk-in/phone
        // sales there's no real "country" the customer entered, so we
        // derive a sensible value from where the parts themselves are
        // located — this is never null and always a legitimate value.
        $firstPartLocation = $parts[0]['part']->location ?? '';
        $derivedCountry = match(true) {
            str_contains($firstPartLocation, 'Nigeria') => 'Nigeria',
            str_contains($firstPartLocation, 'Ghana')   => 'Ghana',
            default                                      => 'USA',
        };

        DB::beginTransaction();
        try {
            $orderId = DB::table('orders')->insertGetId([
                'order_ref'           => $orderRef,
                'channel'             => $request->fulfillment_type === 'Collection' ? 'walk-in' : 'phone',
                'customer_name'       => $request->customer_name,
                'customer_phone'      => $request->customer_phone,
                'customer_whatsapp'   => $request->customer_whatsapp ?: $request->customer_phone,
                'customer_email'      => $request->customer_email,
                'customer_city'       => $request->customer_city,
                'customer_country'    => $request->customer_country ?: $derivedCountry,
                'fulfillment_type'    => $request->fulfillment_type,
                'delivery_address'    => $request->delivery_address,
                'payment_method'      => $request->payment_method,
                'payment_status'      => $paymentReceived ? 'confirmed' : 'awaiting_payment',
                'order_status'        => $paymentReceived ? 'confirmed' : 'awaiting_payment',
                'payment_confirmed_at'=> $paymentReceived ? now() : null,
                'confirmed_by'        => $paymentReceived ? (Session::get('staff_name') ?? 'Admin') : null,
                'total_amount_ngn'    => round($totalNgn),
                'total_amount_usd'    => round($totalUsd, 2),
                'exchange_rate'       => $rates['NGN'],
                'notes'               => $request->notes,
                'staff_notes'         => 'Placed in-person/by-phone by ' . (Session::get('staff_name') ?? 'Admin'),
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            foreach ($parts as $p) {
                $part = $p['part'];
                $priceLocal = $part->price_local ?? $part->price_usd;
                $currencyCode = $part->currency_code ?? 'USD';

                $unitNgn = $currencyCode === 'NGN' ? $priceLocal : ($currencyCode === 'GHS'
                    ? ($priceLocal / $rates['GHS']) * $rates['NGN']
                    : $priceLocal * $rates['NGN']);
                $unitUsd = $currencyCode === 'USD' ? $priceLocal : ($currencyCode === 'GHS'
                    ? $priceLocal / $rates['GHS']
                    : $priceLocal / $rates['NGN']);

                DB::table('order_items')->insert([
                    'order_id'        => $orderId,
                    'part_id'         => $part->id,
                    'part_name'       => $part->part_name,
                    'part_code'       => $part->part_code,
                    'brand'           => $part->brand,
                    'model'           => $part->model,
                    'year_from'       => $part->year_from,
                    'year_to'         => $part->year_to,
                    'condition_grade' => $part->condition_grade,
                    'location'        => $part->location,
                    'unit_price_ngn'  => round($unitNgn),
                    'unit_price_usd'  => round($unitUsd, 2),
                    'subtotal_ngn'    => round($unitNgn * $p['qty']),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                // Reserve the part — same convention as the online
                // checkout flow (Reserved -> Sold on completion, or
                // released back to Available on cancellation).
                DB::table('parts_inventory')->where('id', $part->id)->update([
                    'status'     => 'Reserved',
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Order could not be placed: ' . $e->getMessage());
        }

        return redirect()->route('admin.orders.show', $orderId)
            ->with('success', "Order {$orderRef} placed successfully.");
    }
}
