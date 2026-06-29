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
use App\Http\Controllers\Admin\InvoiceController;

class AdminOrderController extends Controller
{
    const LOCATIONS = [
        'Waxahachie TX', 'Kennedale TX', 'Elkhorn WI',
        'Ile-Ife Nigeria', 'Ibadan Nigeria', 'Lagos Nigeria', 'Abuja Nigeria', 'Akure Nigeria', 'Accra Ghana',
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
            ->get()
            ->map(fn($p) => (array) $p + ['item_type' => 'part']);

        // ── #16 — Place Order should also support Quick Service /
        // Service Rates / Consumables, not just parts. Services have
        // no location-specific stock, so they're always shown
        // regardless of the selected location (currency display
        // matches whatever location is currently selected).
        $servicesQuery = DB::table('service_rates')->where('is_active', true);
        if ($q !== '') {
            $servicesQuery->where(function ($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                   ->orWhere('service_code', 'like', "%{$q}%")
                   ->orWhere('category', 'like', "%{$q}%");
            });
        }
        $resolvedLoc = $loc ?: 'Waxahachie TX';
        $currencyForLoc = InvoiceController::currencyForLocation($resolvedLoc);
        $services = $servicesQuery->select('id', 'service_code', 'name', 'category', 'default_price')
            ->orderBy('name')->limit(30)->get()
            ->map(function ($s) use ($resolvedLoc, $currencyForLoc) {
                $priced = \App\Http\Controllers\Admin\ServiceRateController::priceForLocation($s->id, $resolvedLoc);
                return [
                    'id' => $s->id, 'part_code' => $s->service_code, 'part_name' => $s->name,
                    'brand' => $s->category, 'model' => null, 'year_from' => null, 'year_to' => null,
                    'condition_grade' => null, 'location' => $resolvedLoc, 'price_local' => $priced['price'],
                    'currency_code' => $priced['currency_code'], 'price_usd' => null, 'stock_qty' => null,
                    'item_type' => 'service', 'price_not_set' => !$priced['is_set'],
                ];
            });

        return response()->json(['parts' => $parts->concat($services)->values()]);
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
            'items.*.item_type'=> 'required|in:part,service',
            'items.*.id'       => 'required|integer',
            'items.*.qty'      => 'required|integer|min:1',
        ]);

        // ── STOCK ENFORCEMENT for parts; services have no stock to
        // check (#16 — services can now be added alongside parts).
        $stockErrors = [];
        $lineItems = []; // unified: each entry tagged 'part' or 'service'
        $firstPartLocation = null;

        foreach ($request->items as $item) {
            if ($item['item_type'] === 'service') {
                $service = DB::table('service_rates')->where('id', $item['id'])->where('is_active', true)->first();
                if (!$service) { $stockErrors[] = "A selected service no longer exists."; continue; }
                $lineItems[] = ['type' => 'service', 'service' => $service, 'qty' => (int) $item['qty']];
                continue;
            }

            $part = DB::table('parts_inventory')->where('id', $item['id'])->first();
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
            $lineItems[] = ['type' => 'part', 'part' => $part, 'qty' => $qty];
            $firstPartLocation = $firstPartLocation ?: $part->location;
        }

        if (!empty($stockErrors)) {
            return back()->withInput()->with('error', 'Cannot place this order — ' . implode(' | ', $stockErrors));
        }
        if (empty($lineItems)) {
            return back()->withInput()->with('error', 'No valid items to order.');
        }

        // ── Totals — NO conversion, ever. Every item already has its
        // own real, fixed price_local in its own real currency (set
        // once at harvest/entry time). An order has ONE location, so
        // ONE real currency — we just sum the real numbers directly.
        $primaryLocation = $firstPartLocation ?: $request->get('location', 'Waxahachie TX');
        $orderCurrencyCode = InvoiceController::currencyForLocation($primaryLocation)['code'];
        $totalLocal = 0;

        foreach ($lineItems as $li) {
            if ($li['type'] === 'part') {
                $priceLocal = $li['part']->price_local ?? $li['part']->price_usd;
            } else {
                $priced = \App\Http\Controllers\Admin\ServiceRateController::priceForLocation($li['service']->id, $primaryLocation);
                $priceLocal = $priced['price'];
            }
            $totalLocal += $priceLocal * $li['qty'];
        }

        $year = date('Y');
        $seq  = DB::table('orders')->whereYear('created_at', $year)->count() + 1;
        $orderRef = "AZ-{$year}-" . str_pad($seq, 5, '0', STR_PAD_LEFT);

        $paymentReceived = $request->boolean('payment_received');

        $derivedCountry = match(true) {
            str_contains($primaryLocation, 'Nigeria') => 'Nigeria',
            str_contains($primaryLocation, 'Ghana')   => 'Ghana',
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
                // ── Fixed currency — no conversion, ever. The order has
                // ONE real total in ONE real currency, matching wherever
                // the parts/services actually are. No fabricated NGN
                // figure for a USA order, no fabricated USD figure for
                // a Nigeria order.
                'total_amount_local'  => round($totalLocal, 2),
                'currency_code'       => $orderCurrencyCode,
                'total_amount_ngn'    => $orderCurrencyCode === 'NGN' ? round($totalLocal) : null,
                'total_amount_usd'    => $orderCurrencyCode === 'USD' ? round($totalLocal, 2) : null,
                'notes'               => $request->notes,
                'staff_notes'         => 'Placed in-person/by-phone by ' . (Session::get('staff_name') ?? 'Admin'),
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            foreach ($lineItems as $li) {
                if ($li['type'] === 'part') {
                    $part = $li['part'];
                    $priceLocal = $part->price_local ?? $part->price_usd;
                    $itemCurrency = $part->currency_code ?? 'USD';

                    DB::table('order_items')->insert([
                        'order_id'        => $orderId,
                        'item_type'       => 'part',
                        'part_id'         => $part->id,
                        'service_id'      => null,
                        'part_name'       => $part->part_name,
                        'part_code'       => $part->part_code,
                        'brand'           => $part->brand,
                        'model'           => $part->model,
                        'year_from'       => $part->year_from,
                        'year_to'         => $part->year_to,
                        'condition_grade' => $part->condition_grade,
                        'location'        => $part->location,
                        // No conversion — store the part's own real price,
                        // in its own real currency, in the matching column.
                        'unit_price_local'=> $priceLocal,
                        'subtotal_local'  => round($priceLocal * $li['qty'], 2),
                        'unit_price_ngn'  => $itemCurrency === 'NGN' ? round($priceLocal) : null,
                        'unit_price_usd'  => $itemCurrency === 'USD' ? round($priceLocal, 2) : null,
                        'subtotal_ngn'    => $itemCurrency === 'NGN' ? round($priceLocal * $li['qty']) : null,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);

                    DB::table('parts_inventory')->where('id', $part->id)->update([
                        'status'     => 'Reserved',
                        'updated_at' => now(),
                    ]);
                } else {
                    $service = $li['service'];
                    $priced = \App\Http\Controllers\Admin\ServiceRateController::priceForLocation($service->id, $primaryLocation);
                    $priceLocal = $priced['price'];
                    $itemCurrency = $priced['currency_code'];

                    DB::table('order_items')->insert([
                        'order_id'        => $orderId,
                        'item_type'       => 'service',
                        'part_id'         => null,
                        'service_id'      => $service->id,
                        'part_name'       => $service->name,
                        'part_code'       => $service->service_code,
                        'brand'           => $service->category,
                        'model'           => null,
                        'year_from'       => null,
                        'year_to'         => null,
                        'condition_grade' => null,
                        'location'        => $primaryLocation,
                        'unit_price_local'=> $priceLocal,
                        'subtotal_local'  => round($priceLocal * $li['qty'], 2),
                        'unit_price_ngn'  => $itemCurrency === 'NGN' ? round($priceLocal) : null,
                        'unit_price_usd'  => $itemCurrency === 'USD' ? round($priceLocal, 2) : null,
                        'subtotal_ngn'    => $itemCurrency === 'NGN' ? round($priceLocal * $li['qty']) : null,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                    // No inventory to reserve for a service
                }
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
