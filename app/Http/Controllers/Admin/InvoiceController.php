<?php
// FILE: app/Http/Controllers/Admin/InvoiceController.php
//
// UPDATED: Fixed-currency pricing. price_local + currency_code are now the
// authoritative, never-recalculated values (set once at creation/harvest
// time, based on the location). price_usd is kept ONLY as a frozen
// historical snapshot for cross-location $ reference — it is NEVER used
// for display or live conversion anymore. formatPrice() now formats
// whatever currency a record was actually priced in; it does not convert.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class InvoiceController extends Controller
{
    // =========================================================
    // CURRENCY HELPERS — static so HarvestController can call them
    // =========================================================

    // Still used to DETERMINE which currency a NEW record should be
    // priced in, based on its location. Once set, that currency is
    // fixed for that record forever — this is not used to convert
    // existing records on the fly anymore.
    public static function currencyForLocation(string $location): array
    {
        $loc = strtolower(trim($location));

        $ngKeywords = [
            'nigeria','lagos','ife','ile-ife','ibadan','oshodi',
            'abuja','kano','ph','port harcourt','benin','enugu',
            'ogun','abeokuta','ilorin','kaduna','jos',
        ];
        $ghKeywords = ['ghana','accra','kumasi','takoradi','tema'];
        $ukKeywords = ['uk','united kingdom','london','manchester','birmingham'];

        foreach ($ngKeywords as $kw) {
            if (str_contains($loc, $kw)) {
                return ['code' => 'NGN', 'symbol' => '₦', 'rate' => 1600, 'decimals' => 0];
            }
        }
        foreach ($ghKeywords as $kw) {
            if (str_contains($loc, $kw)) {
                return ['code' => 'GHS', 'symbol' => 'GH₵', 'rate' => 15.5, 'decimals' => 2];
            }
        }
        foreach ($ukKeywords as $kw) {
            if (str_contains($loc, $kw)) {
                return ['code' => 'GBP', 'symbol' => '£', 'rate' => 0.79, 'decimals' => 2];
            }
        }

        return ['code' => 'USD', 'symbol' => '$', 'rate' => 1, 'decimals' => 2];
    }

    // Maps a currency CODE back to its display symbol/decimals — used
    // when formatting an already-fixed price_local + currency_code pair,
    // with NO conversion happening (rate is irrelevant here).
    public static function currencyMeta(string $code): array
    {
        return match ($code) {
            'NGN' => ['code' => 'NGN', 'symbol' => '₦', 'decimals' => 0],
            'GHS' => ['code' => 'GHS', 'symbol' => 'GH₵', 'decimals' => 2],
            'GBP' => ['code' => 'GBP', 'symbol' => '£', 'decimals' => 2],
            default => ['code' => 'USD', 'symbol' => '$', 'decimals' => 2],
        };
    }

    // Formats a FIXED local price with its own currency's symbol —
    // no conversion, no rate involved. This is the only formatter
    // that should be used for displaying parts/invoice prices now.
    public static function formatLocal(float $priceLocal, string $currencyCode): string
    {
        $meta = self::currencyMeta($currencyCode);
        return $meta['symbol'] . number_format($priceLocal, $meta['decimals']);
    }

    // ── Legacy formatter, kept only for any code path still passing
    // a USD snapshot + rate table. New code should use formatLocal().
    public static function formatPrice(float $usdPrice, array $currency): string
    {
        $amount = $usdPrice * $currency['rate'];
        return $currency['symbol'] . number_format($amount, $currency['decimals']);
    }

    // =========================================================
    // BUSINESS INFO BY LOCATION
    // =========================================================
    private function getBusinessInfo(string $location): array
{
    $loc = strtolower($location);

    if (str_contains($loc, 'oshodi') || str_contains($loc, 'lagos')) {
        return [
            'company'   => 'Gasbok Engineering Nig. Limited',
            'rc'        => 'RC: 1135830',
            'address'   => 'Oshodi, Lagos State, Nigeria',
            'phone'     => '+234 915 568 8804',
            'email'     => 'lagos@autozenithparts.com',
            'bank'      => 'Bank Transfer',
            'account'   => '5085726530',
            'acct_name' => 'Gasbok Engineering Nigeria Limited',
            'warranty'  => '10 days',
        ];
    }

    if (str_contains($loc, 'nigeria') || str_contains($loc, 'ife') || str_contains($loc, 'ibadan')) {
        return [
            'company'   => 'Gasbok Engineering Nig. Limited',
            'rc'        => 'RC: 1135830',
            'address'   => 'Ile-Ife, Osun State, Nigeria',
            'phone'     => '+234 915 568 8804',
            'email'     => 'ng@autozenithparts.com',
            'bank'      => 'Bank Transfer',
            'account'   => '5085726530',
            'acct_name' => 'Gasbok Engineering Nigeria Limited',
            'warranty'  => '10 days',
        ];
    }

    if (str_contains($loc, 'ghana') || str_contains($loc, 'accra')) {
        return [
            'company'   => 'Auto Zenith Parts — Ghana Office',
            'rc'        => null,
            'address'   => 'Accra, Ghana',
            'phone'     => '+233 XXX XXX XXXX',
            'email'     => 'gh@autozenithparts.com',
            'bank'      => 'Bank Transfer',
            'account'   => 'On request',
            'acct_name' => 'Auto Zenith Parts Ghana',
            'warranty'  => '10 days',
        ];
    }

    if (str_contains($loc, 'elkhorn') || str_contains($loc, 'wi')) {
        return [
            'company'   => 'Auto Zenith LLC',
            'rc'        => null,
            'address'   => '613 E Geneva St #23, Elkhorn WI 53121',
            'phone'     => '+1 512 587 3425',
            'email'     => 'wi@autozenithparts.com',
            'bank'      => 'Zelle / CashApp / Venmo',
            'account'   => 'Zelle: 5125873425 | CashApp: $GASBOK | Venmo: GASBOK',
            'acct_name' => 'Auto Zenith LLC',
            'warranty'  => '15 days',
        ];
    }

    // Default: Waxahachie TX / Kennedale TX
    return [
        'company'   => 'Accolade Autos and General LLC',
        'rc'        => null,
        'address'   => 'Waxahachie TX 75165',
        'phone'     => '+1 512 587 3425',
        'email'     => 'info@autozenithparts.com',
        'bank'      => 'Zelle / CashApp / Venmo',
        'account'   => 'Zelle: 5125873425 | CashApp: $GASBOK | Venmo: GASBOK',
        'acct_name' => 'Accolade Autos and General LLC',
        'warranty'  => '15 days',
    ];
}

    // =========================================================
    // Commission helper — credits the creating staff member if they
    // are a sales_rep. Uses commission_tiers (volume-based, evaluated
    // against the rep's running total this calendar month in their
    // own currency) if set, falling back to commission_base_percent.
    // =========================================================
    private function creditCommissionIfApplicable(int $invoiceId, float $saleAmountLocal, string $currencyCode): void
    {
        $staffId = Session::get('staff_id');
        if (!$staffId) return;

        $staff = DB::table('staff')->where('id', $staffId)->first();
        if (!$staff || $staff->role !== 'sales_rep') return;

        $percent = (float) ($staff->commission_base_percent ?? 0);

        if ($staff->commission_tiers) {
            $tiers = json_decode($staff->commission_tiers, true);
            if (is_array($tiers) && count($tiers) > 0) {
                // Running volume this calendar month, same currency, sale-type entries only
                $monthVolume = (float) DB::table('sales_commissions')
                    ->where('staff_id', $staffId)
                    ->where('currency_code', $currencyCode)
                    ->where('type', 'sale')
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->sum('sale_amount_local');

                $projectedVolume = $monthVolume + $saleAmountLocal;

                // Pick the highest tier whose min_volume the projected volume has reached
                usort($tiers, fn($a, $b) => $a['min_volume'] <=> $b['min_volume']);
                foreach ($tiers as $tier) {
                    if ($projectedVolume >= ($tier['min_volume'] ?? 0)) {
                        $percent = (float) ($tier['percent'] ?? $percent);
                    }
                }
            }
        }

        if ($percent <= 0) return;

        $commissionAmount = round($saleAmountLocal * ($percent / 100), 2);

        DB::table('sales_commissions')->insert([
            'staff_id'                 => $staffId,
            'invoice_id'               => $invoiceId,
            'currency_code'            => $currencyCode,
            'sale_amount_local'        => $saleAmountLocal,
            'commission_percent'       => $percent,
            'commission_amount_local'  => $commissionAmount,
            'type'                     => 'sale',
            'created_at'               => now(),
            'updated_at'               => now(),
        ]);
    }

    // =========================================================
    // SHOW — Invoice from an existing Order
    // GET /admin/orders/{id}/invoice
    // =========================================================
    public function show(int $orderId)
    {
        $order = DB::table('orders')->where('id', $orderId)->first();
        if (!$order) abort(404);

        $items = DB::table('order_items as oi')
            ->join('parts_inventory as p', 'p.id', '=', 'oi.part_id')
            ->where('oi.order_id', $orderId)
            ->select(
                'oi.unit_price_usd',
                'oi.unit_price_ngn',
                'oi.subtotal_ngn',
                'p.price_local',
                'p.currency_code',
                'p.part_name',
                'p.part_code',
                'p.brand',
                'p.model',
                'p.year_from',
                'p.year_to',
                'p.condition_grade',
                'p.location as part_location',
                'p.engine_code_oem',
                'p.part_category'
            )->get()
            ->map(function ($item) {
                // order_items has no qty column — each row's qty is
                // implied by subtotal_ngn / unit_price_ngn (both exist
                // and are always set together at order creation time).
                $item->qty = ($item->subtotal_ngn && $item->unit_price_ngn)
                    ? max(1, round($item->subtotal_ngn / $item->unit_price_ngn))
                    : 1;
                return $item;
            });

        $saleLocation = $order->location
            ?? ($items->first()->part_location ?? 'Waxahachie TX');

        // Use the FIXED currency already stamped on the parts themselves
        // (they were priced once at harvest/entry time and never change).
        $currencyCode = $items->first()->currency_code ?? self::currencyForLocation($saleLocation)['code'];
        $businessInfo = $this->getBusinessInfo($saleLocation);

        $subtotalLocal = 0;
        $lineItems = $items->map(function ($item) use (&$subtotalLocal) {
            $unitLocal     = $item->price_local ?? $item->unit_price_usd; // fallback for pre-migration rows
            $lineLocal     = $unitLocal * $item->qty;
            $subtotalLocal += $lineLocal;
            return (object) array_merge((array) $item, [
                'unit_price_fmt' => self::formatLocal($unitLocal, $item->currency_code ?? 'USD'),
                'total_fmt'      => self::formatLocal($lineLocal, $item->currency_code ?? 'USD'),
            ]);
        });

        $subtotalFmt = self::formatLocal($subtotalLocal, $currencyCode);
        $invoiceNo   = 'AZP-' . date('Y') . '-' . str_pad($orderId, 5, '0', STR_PAD_LEFT);

        $customerInfo = (object)[
            'name'    => $order->customer_name ?? '',
            'phone'   => $order->customer_phone ?? '',
            'email'   => $order->customer_email ?? '',
            'address' => $order->customer_address ?? '',
        ];

        $currency      = self::currencyMeta($currencyCode); // for blade templates expecting ['symbol'=>..,'code'=>..]
        $location      = $saleLocation;
        $createdAt     = $order->created_at ?? now();
        $paymentMethod = $order->payment_method ?? 'Cash';
        $copyKey       = 'customer';
        $subtotalUsd   = $subtotalLocal; // kept for template compatibility; NOT a real USD conversion anymore

        return view('admin.invoices.show', compact(
            'order', 'lineItems', 'currency', 'subtotalFmt',
            'subtotalUsd', 'invoiceNo', 'businessInfo', 'saleLocation',
            'location', 'createdAt', 'customerInfo', 'paymentMethod', 'copyKey'
        ));
    }

    // =========================================================
    // HARVEST INVOICE — from a completed harvest session
    // GET /admin/harvest/{id}/invoice
    // =========================================================
    public function harvest(int $harvestId)
    {
        $harvest = DB::table('harvest_sessions as hs')
            ->join('donor_vehicles as dv', 'dv.id', '=', 'hs.donor_vehicle_id')
            ->join('staff as s', 's.id', '=', 'hs.staff_id')
            ->where('hs.id', $harvestId)
            ->select(
                'hs.*',
                'dv.make', 'dv.model', 'dv.year', 'dv.vin',
                'dv.location as vehicle_location',
                's.name as staff_name'
            )->first();

        if (!$harvest) abort(404);

        $parts = DB::table('parts_inventory')
            ->where('donor_vin', $harvest->vin)
            ->orderBy('part_category')
            ->get();

        $harvestLocation = $harvest->location ?? $harvest->vehicle_location ?? 'Waxahachie TX';
        $currencyCode    = $parts->first()->currency_code ?? self::currencyForLocation($harvestLocation)['code'];
        $currency        = self::currencyMeta($currencyCode);
        $businessInfo    = $this->getBusinessInfo($harvestLocation);

        $lineItems = $parts->map(function ($p) {
            $priceLocal = $p->price_local ?? $p->price_usd; // fallback for pre-migration rows
            return (object)[
                'part_name'       => $p->part_name,
                'part_code'       => $p->part_code,
                'brand'           => $p->brand,
                'model'           => $p->model,
                'year_from'       => $p->year_from,
                'year_to'         => $p->year_to,
                'condition_grade' => $p->condition_grade,
                'qty'             => 1,
                'unit_fmt'        => self::formatLocal($priceLocal, $p->currency_code ?? 'USD'),
                'total_fmt'       => self::formatLocal($priceLocal, $p->currency_code ?? 'USD'),
                'price_usd'       => $priceLocal, // kept for template compatibility
            ];
        });

        $subtotalLocal = $parts->sum(fn($p) => $p->price_local ?? $p->price_usd);
        $subtotalFmt   = self::formatLocal($subtotalLocal, $currencyCode);
        $subtotalUsd   = $subtotalLocal; // kept for template compatibility
        $invoiceNo     = 'HVSN-' . str_pad($harvestId, 5, '0', STR_PAD_LEFT);

        return view('admin.invoices.harvest', compact(
            'harvest', 'lineItems', 'currency', 'subtotalFmt',
            'subtotalUsd', 'invoiceNo', 'businessInfo', 'harvestLocation'
        ));
    }

    // =========================================================
    // MANUAL INVOICE — Walk-in / Staff created
    // GET /admin/invoices/manual/create
    // =========================================================
    public function createManual()
    {
        $parts = DB::table('parts_inventory')
            ->where('status', 'Available')
            ->orderBy('brand')->orderBy('part_name')
            ->get(['id','part_code','part_name','brand','model',
                   'year_from','year_to','price_local','currency_code','price_usd','condition_grade','location']);

        $locations = [
            'Waxahachie TX'    => 'Waxahachie TX — USD ($)',
            'Kennedale TX'     => 'Kennedale TX — USD ($)',
            'Elkhorn WI'       => 'Elkhorn WI — USD ($)',
            'Ile-Ife Nigeria'  => 'Ile-Ife, Nigeria — NGN (₦)',
            'Ibadan Nigeria'   => 'Ibadan, Nigeria — NGN (₦)',
            'Lagos Nigeria'     => 'Lagos, Nigeria — NGN (₦)',
            'Abuja Nigeria'     => 'Abuja, Nigeria — NGN (₦)',
            'Akure Nigeria'     => 'Akure, Nigeria — NGN (₦)',
            'Accra Ghana'      => 'Accra, Ghana — GHS (GH₵)',
        ];

        $staffLocation = Session::get('staff_location');
        $isAdmin       = Session::get('staff_role') === 'admin';

        $currentStaff = DB::table('staff')->where('id', Session::get('staff_id'))->first();
        $staffDiscountCapFixed   = $currentStaff->discount_cap_fixed ?? null;
        $staffDiscountCapPercent = $currentStaff->discount_cap_percent ?? null;

        // #18 — Manual Invoice should be able to add Service Rates
        // alongside parts, not just inventory items.
        $serviceRates = DB::table('service_rates')->where('is_active', true)
            ->orderBy('category')->orderBy('name')->get();

        return view('admin.invoices.manual', compact(
            'parts', 'locations', 'staffLocation', 'isAdmin',
            'staffDiscountCapFixed', 'staffDiscountCapPercent', 'serviceRates'
        ));
    }
    // =========================================================
    // GET /admin/invoices/manual/{id}/edit — admin/manager only
    // =========================================================
    public function editManual(int $id)
    {
        if (!in_array(Session::get('staff_role'), ['admin', 'manager'])) {
            abort(403, 'Only admin or manager accounts can edit invoices.');
        }

        $invoice = DB::table('invoices')->where('id', $id)->first();
        if (!$invoice) abort(404);

        $items = DB::table('invoice_items')->where('invoice_id', $id)->get();

        $parts = DB::table('parts_inventory')
            ->where('status', 'Available')
            ->orderBy('brand')->orderBy('part_name')
            ->get(['id','part_code','part_name','brand','model',
                   'year_from','year_to','price_local','currency_code','price_usd','condition_grade','location']);

        $locations = [
            'Waxahachie TX'    => 'Waxahachie TX — USD ($)',
            'Kennedale TX'     => 'Kennedale TX — USD ($)',
            'Elkhorn WI'       => 'Elkhorn WI — USD ($)',
            'Ile-Ife Nigeria'  => 'Ile-Ife, Nigeria — NGN (₦)',
            'Ibadan Nigeria'   => 'Ibadan, Nigeria — NGN (₦)',
            'Lagos Nigeria'     => 'Lagos, Nigeria — NGN (₦)',
            'Abuja Nigeria'     => 'Abuja, Nigeria — NGN (₦)',
            'Akure Nigeria'     => 'Akure, Nigeria — NGN (₦)',
            'Accra Ghana'      => 'Accra, Ghana — GHS (GH₵)',
        ];

        $staffLocation = Session::get('staff_location');
        $isAdmin       = true;

        $currentStaff = DB::table('staff')->where('id', Session::get('staff_id'))->first();
        $staffDiscountCapFixed   = $currentStaff->discount_cap_fixed ?? null;
        $staffDiscountCapPercent = $currentStaff->discount_cap_percent ?? null;

        // Currency is now FIXED on the invoice itself — never recalculated
        $currencyCode = $invoice->currency_code ?? self::currencyForLocation($invoice->location)['code'];
        $currency     = self::currencyMeta($currencyCode);

        $existingItemsJson = $items->map(function ($i) {
            $priceLocal = $i->unit_price_local ?? $i->unit_price_usd; // fallback for pre-migration rows
            return [
                'name'           => $i->part_name,
                'part_id'        => $i->part_id,
                'price'          => $priceLocal,
                'grade'          => $i->condition_grade,
                'qty'            => $i->qty,
                'discount_value' => $i->discount_value,
                'discount_type'  => $i->discount_type ?? 'fixed',
            ];
        })->values()->toJson();

        return view('admin.invoices.manual-edit', compact(
            'invoice', 'items', 'parts', 'locations', 'staffLocation', 'isAdmin',
            'staffDiscountCapFixed', 'staffDiscountCapPercent', 'currency', 'existingItemsJson'
        ));
    }
    // =========================================================
    // PUT /admin/invoices/manual/{id} — admin/manager only
    // =========================================================
    public function updateManual(Request $request, int $id)
    {
        if (!in_array(Session::get('staff_role'), ['admin', 'manager'])) {
            abort(403, 'Only admin or manager accounts can edit invoices.');
        }

        $invoice = DB::table('invoices')->where('id', $id)->first();
        if (!$invoice) abort(404);

        $request->validate([
            'customer_name'    => 'required|string|max:120',
            'location'         => 'required|string',
            'items'            => 'required|array|min:1',
            'items.*.name'     => 'required|string',
            'items.*.price'    => 'required|numeric|min:0',
            'items.*.qty'      => 'required|integer|min:1',
        ]);

        $saleLocation = $request->location;
        $currencyCode = self::currencyForLocation($saleLocation)['code'];

        // ── Prices entered here are now treated as FIXED local-currency
        // values — no division by a rate, no conversion to USD storage.
        $lineItems = collect($request->items)->map(function ($item) {
            $priceLocal   = (float) $item['price'];
            $qty          = (int) $item['qty'];
            $lineGrossLocal = $priceLocal * $qty;

            $discType  = $item['discount_type'] ?? 'fixed';
            $discValue = (float) ($item['discount_value'] ?? 0);
            $discLocal = 0;
            if ($discValue > 0) {
                $discLocal = $discType === 'percent'
                    ? $lineGrossLocal * ($discValue / 100)
                    : min($discValue, $lineGrossLocal);
            }
            $lineLocal = $lineGrossLocal - $discLocal;

            $part = !empty($item['part_id'])
                ? DB::table('parts_inventory')->find($item['part_id'])
                : null;

            return (object)[
                'part_name'             => $item['name'],
                'part_code'             => $part->part_code ?? 'MANUAL',
                'brand'                 => $part->brand ?? '',
                'model'                 => $part->model ?? '',
                'condition_grade'       => $part->condition_grade ?? ($item['grade'] ?? 'B'),
                'qty'                   => $qty,
                'unit_price_local'      => $priceLocal,
                'discount_type'         => $discValue > 0 ? $discType : null,
                'discount_value'        => $discValue > 0 ? $discValue : null,
                'discount_amount_local' => $discLocal,
                'line_local'            => $lineLocal,
            ];
        });

        $subtotalAfterLineDiscounts = $lineItems->sum('line_local');

        $invoiceDiscType  = $request->invoice_discount_type ?? 'fixed';
        $invoiceDiscValue = (float) ($request->invoice_discount_value ?? 0);
        $invoiceDiscLocal = 0;
        if ($invoiceDiscValue > 0) {
            $invoiceDiscLocal = $invoiceDiscType === 'percent'
                ? $subtotalAfterLineDiscounts * ($invoiceDiscValue / 100)
                : min($invoiceDiscValue, $subtotalAfterLineDiscounts);
        }

        $newSubtotalLocal = $subtotalAfterLineDiscounts - $invoiceDiscLocal;
        $totalDiscountLocal = $lineItems->sum('discount_amount_local') + $invoiceDiscLocal;

        // ── Build a human-readable change summary before overwriting ──
        $changes = [];
        if ($invoice->customer_name !== $request->customer_name) {
            $changes[] = "Customer name: \"{$invoice->customer_name}\" → \"{$request->customer_name}\"";
        }
        if ($invoice->location !== $saleLocation) {
            $changes[] = "Location: \"{$invoice->location}\" → \"{$saleLocation}\"";
        }
        $oldSubtotalLocal = $invoice->subtotal_local ?? $invoice->subtotal_usd;
        if (round((float) $oldSubtotalLocal, 2) !== round($newSubtotalLocal, 2)) {
            $changes[] = "Subtotal: " . self::formatLocal($oldSubtotalLocal, $invoice->currency_code ?? $currencyCode)
                . " → " . self::formatLocal($newSubtotalLocal, $currencyCode);
        }
        $oldItemCount = DB::table('invoice_items')->where('invoice_id', $id)->count();
        if ($oldItemCount !== $lineItems->count()) {
            $changes[] = "Item count: {$oldItemCount} → {$lineItems->count()}";
        }
        $changesSummary = $changes ? implode('; ', $changes) : 'No substantive changes detected.';

        // ── Apply the update ───────────────────────────────────────
        DB::table('invoices')->where('id', $id)->update([
            'customer_name'             => $request->customer_name,
            'customer_phone'            => $request->customer_phone ?? '',
            'customer_email'            => $request->customer_email ?? '',
            'customer_address'          => $request->customer_address ?? '',
            'location'                  => $saleLocation,
            'currency_code'             => $currencyCode,
            'subtotal_local'            => $newSubtotalLocal,
            'subtotal_usd'              => $newSubtotalLocal, // kept for template compatibility — not a real $ value
            'discount_amount_local'     => $totalDiscountLocal,
            'discount_amount_usd'       => $totalDiscountLocal, // kept for template compatibility
            'discount_type'             => $invoiceDiscValue > 0 ? $invoiceDiscType : null,
            'discount_value'            => $invoiceDiscValue > 0 ? $invoiceDiscValue : null,
            'notes'                     => $request->notes ?? null,
            'updated_at'                => now(),
        ]);

        // Replace items wholesale — simpler and safer than diffing rows
        DB::table('invoice_items')->where('invoice_id', $id)->delete();
        foreach ($lineItems as $li) {
            DB::table('invoice_items')->insert([
                'invoice_id'             => $id,
                'part_id'                => null,
                'part_name'              => $li->part_name,
                'part_code'              => $li->part_code,
                'brand'                  => $li->brand,
                'model'                  => $li->model,
                'condition_grade'        => $li->condition_grade,
                'qty'                    => $li->qty,
                'unit_price_local'       => $li->unit_price_local,
                'unit_price_usd'         => $li->unit_price_local, // kept for template compatibility
                'discount_amount_local'  => $li->discount_amount_local,
                'discount_amount_usd'    => $li->discount_amount_local, // kept for template compatibility
                'discount_type'          => $li->discount_type,
                'discount_value'         => $li->discount_value,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        }

        // ── Log the edit ───────────────────────────────────────────
        DB::table('invoice_edit_log')->insert([
            'invoice_id'       => $id,
            'edited_by'        => Session::get('staff_name') ?? 'Unknown',
            'changes_summary'  => $changesSummary,
            'created_at'       => now(),
        ]);

        return redirect()->route('admin.invoices.show.manual', $id)
            ->with('success', 'Invoice updated successfully. Changes have been logged.');
    }
    // =========================================================
    // POST /admin/invoices/manual — Store + show invoice
    // =========================================================
    public function storeManual(Request $request)
    {
        $request->validate([
            'customer_name'    => 'required|string|max:120',
            'location'         => 'required|string',
            'items'            => 'required|array|min:1',
            'items.*.name'     => 'required|string',
            'items.*.price'    => 'required|numeric|min:0',
            'items.*.qty'      => 'required|integer|min:1',
        ]);

        $saleLocation = $request->location;
        $currencyCode = self::currencyForLocation($saleLocation)['code'];
        $currency     = self::currencyMeta($currencyCode);
        $businessInfo = $this->getBusinessInfo($saleLocation);

        // ── Prices entered here are FIXED local-currency values now —
        // no division by a rate, no USD conversion at storage time.
        $lineItems = collect($request->items)->map(function ($item) use ($currencyCode) {
            $priceLocal     = (float) $item['price'];
            $qty            = (int) $item['qty'];
            $lineGrossLocal = $priceLocal * $qty;

            $discType  = $item['discount_type'] ?? 'fixed';
            $discValue = (float) ($item['discount_value'] ?? 0);
            $discLocal = 0;
            if ($discValue > 0) {
                $discLocal = $discType === 'percent'
                    ? $lineGrossLocal * ($discValue / 100)
                    : min($discValue, $lineGrossLocal);
            }
            $lineLocal = $lineGrossLocal - $discLocal;

            $part = !empty($item['part_id'])
                ? DB::table('parts_inventory')->find($item['part_id'])
                : null;
            return (object)[
                'part_id'               => $part->id ?? null,
                'part_name'             => $item['name'],
                'part_code'             => $part->part_code ?? 'MANUAL',
                'brand'                 => $part->brand ?? '',
                'model'                 => $part->model ?? '',
                'year_from'             => $part->year_from ?? '',
                'year_to'               => $part->year_to ?? '',
                'part_category'         => $part->part_category ?? '',
                'condition_grade'       => $part->condition_grade ?? ($item['grade'] ?? 'B'),
                'engine_code_oem'       => $part->engine_code_oem ?? '',
                'qty'                   => $qty,
                'unit_price_local'      => $priceLocal,
                'unit_price_fmt'        => self::formatLocal($priceLocal, $currencyCode),
                'discount_type'         => $discValue > 0 ? $discType : null,
                'discount_value'        => $discValue > 0 ? $discValue : null,
                'discount_amount_local' => $discLocal,
                'total_fmt'             => self::formatLocal($lineLocal, $currencyCode),
                'line_local'            => $lineLocal,
            ];
        });

        $subtotalAfterLineDiscounts = $lineItems->sum('line_local');

        $invoiceDiscType  = $request->invoice_discount_type ?? 'fixed';
        $invoiceDiscValue = (float) ($request->invoice_discount_value ?? 0);
        $invoiceDiscLocal = 0;
        if ($invoiceDiscValue > 0) {
            $invoiceDiscLocal = $invoiceDiscType === 'percent'
                ? $subtotalAfterLineDiscounts * ($invoiceDiscValue / 100)
                : min($invoiceDiscValue, $subtotalAfterLineDiscounts);
        }

        $subtotalLocal = $subtotalAfterLineDiscounts - $invoiceDiscLocal;
        $subtotalFmt   = self::formatLocal($subtotalLocal, $currencyCode);

        // ── Discount cap check (server-side, authoritative) ──────────
        // NOTE: discount caps were previously stored/compared in USD.
        // Now compared directly against the LOCAL currency amount.
        // If your discount_cap_fixed values were set assuming USD,
        // they'll need updating per-location — flag this for review.
        $currentStaffForCap = DB::table('staff')->where('id', Session::get('staff_id'))->first();
        $totalDiscountLocal = $lineItems->sum('discount_amount_local') + $invoiceDiscLocal;
        $grossLocal = $subtotalAfterLineDiscounts + $lineItems->sum('discount_amount_local');
        $discountPercentOfGross = $grossLocal > 0 ? ($totalDiscountLocal / $grossLocal) * 100 : 0;

        $exceedsCap = false;
        if ($currentStaffForCap) {
            if ($currentStaffForCap->discount_cap_fixed !== null && $totalDiscountLocal > $currentStaffForCap->discount_cap_fixed) {
                $exceedsCap = true;
            }
            if ($currentStaffForCap->discount_cap_percent !== null && $discountPercentOfGross > $currentStaffForCap->discount_cap_percent) {
                $exceedsCap = true;
            }
        }

        if ($exceedsCap && !$request->filled('discount_override_reason')) {
            return back()->withInput()->with('error',
                'This discount exceeds your allowance cap. Please provide an override reason and resubmit.');
        }

        // ── STOCK ENFORCEMENT — never sell more than what's physically
        // available. Checked again here (authoritative, server-side)
        // even though the form should already prevent this client-side.
        // This is the line that actually protects inventory from theft/
        // loss via under-reported or inflated sales.
        $stockErrors = [];
        foreach ($request->items as $item) {
            if (empty($item['part_id'])) continue; // manually-typed item, not tied to real stock

            $part = DB::table('parts_inventory')->where('id', $item['part_id'])->first();
            $qty  = (int) ($item['qty'] ?? 1);

            if (!$part) {
                $stockErrors[] = "Part for \"{$item['name']}\" no longer exists in inventory.";
                continue;
            }
            if ($part->status !== 'Available') {
                $stockErrors[] = "{$part->part_code} ({$part->part_name}) is no longer Available (current status: {$part->status}).";
                continue;
            }
            if ($qty > $part->stock_qty) {
                $stockErrors[] = "{$part->part_code} ({$part->part_name}): requested {$qty}, but only {$part->stock_qty} in stock.";
            }
        }

        if (!empty($stockErrors)) {
            return back()->withInput()->with('error',
                'Cannot complete this sale — stock check failed: ' . implode(' | ', $stockErrors));
        }
        // ──────────────────────────────────────────────────────────────

        $invoiceNo   = 'AZP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        $customerInfo = (object)[
            'name'    => $request->customer_name,
            'phone'   => $request->customer_phone ?? '',
            'email'   => $request->customer_email ?? '',
            'address' => $request->customer_address ?? '',
        ];

        $location      = $saleLocation;
        $createdAt     = now();
        $paymentMethod = $request->payment_method ?? 'Cash';
        $copyKey       = 'customer';
        $subtotalUsd   = $subtotalLocal; // kept for template compatibility; NOT a real $ conversion

        // ── Persist invoice + items + stock deduction — all in one
        // transaction so a failure partway through never leaves stock
        // decremented without a matching invoice, or vice versa.
        DB::beginTransaction();
        try {
            $invoiceId = DB::table('invoices')->insertGetId([
                'invoice_no'                => $invoiceNo,
                'invoice_type'              => 'parts',
                'customer_name'             => $customerInfo->name,
                'customer_phone'            => $customerInfo->phone,
                'customer_email'            => $customerInfo->email,
                'customer_address'          => $customerInfo->address,
                'location'                  => $saleLocation,
                'currency_code'             => $currencyCode,
                'subtotal_local'            => $subtotalLocal,
                'subtotal_usd'              => $subtotalLocal, // kept for template compatibility
                'discount_amount_local'     => $totalDiscountLocal,
                'discount_amount_usd'       => $totalDiscountLocal, // kept for template compatibility
                'discount_type'             => $invoiceDiscValue > 0 ? $invoiceDiscType : null,
                'discount_value'            => $invoiceDiscValue > 0 ? $invoiceDiscValue : null,
                'discount_override'         => $exceedsCap,
                'discount_override_reason'  => $exceedsCap ? $request->discount_override_reason : null,
                'payment_method'            => $paymentMethod,
                'created_by'                => Session::get('staff_name') ?? 'Admin',
                'notes'                     => $request->notes ?? null,
                'created_at'                => $createdAt,
                'updated_at'                => $createdAt,
            ]);

            foreach ($lineItems as $li) {
                DB::table('invoice_items')->insert([
                    'invoice_id'             => $invoiceId,
                    'part_id'                => $li->part_id,
                    'part_name'              => $li->part_name,
                    'part_code'              => $li->part_code,
                    'brand'                  => $li->brand,
                    'model'                  => $li->model,
                    'condition_grade'        => $li->condition_grade,
                    'qty'                    => $li->qty,
                    'unit_price_local'       => $li->unit_price_local,
                    'unit_price_usd'         => $li->unit_price_local, // kept for template compatibility
                    'discount_amount_local'  => $li->discount_amount_local,
                    'discount_amount_usd'    => $li->discount_amount_local, // kept for template compatibility
                    'discount_type'          => $li->discount_type,
                    'discount_value'         => $li->discount_value,
                    'created_at'             => $createdAt,
                    'updated_at'             => $createdAt,
                ]);

                // ── Deduct stock for real inventory items only. Re-checks
                // availability one more time inside the transaction (in
                // case two staff sold the same last unit simultaneously)
                // and aborts the whole sale if it's no longer sufficient.
                if ($li->part_id) {
                    $part = DB::table('parts_inventory')->where('id', $li->part_id)->lockForUpdate()->first();
                    if (!$part || $part->stock_qty < $li->qty) {
                        throw new \Exception("Stock for {$li->part_code} changed before this sale completed — only " . ($part->stock_qty ?? 0) . " left. Please review and resubmit.");
                    }
                    $newQty = $part->stock_qty - $li->qty;
                    DB::table('parts_inventory')->where('id', $li->part_id)->update([
                        'stock_qty'  => $newQty,
                        'status'     => $newQty <= 0 ? 'Sold' : 'Available',
                        'updated_at' => now(),
                    ]);
                }
            }
            // ──────────────────────────────────────────────────────────

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Sale could not be completed: ' . $e->getMessage());
        }

        // ── Commission — if the staff who created this invoice is a
        // sales_rep, credit them with commission on the net sale.
        // Uses their volume tiers if set, else a flat base %. Computed
        // once at sale time; Phase B returns will insert a NEGATIVE
        // adjustment row referencing this invoice rather than editing
        // this entry, so the ledger stays auditable.
        $this->creditCommissionIfApplicable($invoiceId, $subtotalUsd, $currency['code']);

        return view('admin.invoices.show', compact(
            'lineItems', 'currency', 'subtotalFmt', 'subtotalUsd',
            'invoiceNo', 'businessInfo', 'saleLocation', 'location',
            'createdAt', 'customerInfo', 'paymentMethod', 'copyKey'
        ));
    }

    // =========================================================
    // GET /admin/invoices/service/create — Quick Receipt for services
    // (labor, diagnostic fees, misc charges) — NEVER touches inventory.
    // =========================================================
    public function createService()
    {
        $serviceRates = DB::table('service_rates')->where('is_active', true)
            ->orderBy('category')->orderBy('name')->get();

        $locations = [
            'Waxahachie TX'    => 'Waxahachie TX — USD ($)',
            'Kennedale TX'     => 'Kennedale TX — USD ($)',
            'Elkhorn WI'       => 'Elkhorn WI — USD ($)',
            'Ile-Ife Nigeria'  => 'Ile-Ife, Nigeria — NGN (₦)',
            'Ibadan Nigeria'   => 'Ibadan, Nigeria — NGN (₦)',
            'Lagos Nigeria'     => 'Lagos, Nigeria — NGN (₦)',
            'Abuja Nigeria'     => 'Abuja, Nigeria — NGN (₦)',
            'Akure Nigeria'     => 'Akure, Nigeria — NGN (₦)',
            'Accra Ghana'      => 'Accra, Ghana — GHS (GH₵)',
        ];

        return view('admin.invoices.service', compact('serviceRates', 'locations'));
    }

    // =========================================================
    // AJAX: GET /admin/invoices/service/search-parts?q=&location=
    // Lets Quick Receipt add real Parts/Consumables alongside
    // Quick Service entries (#16).
    // =========================================================
    public function serviceSearchParts(Request $request)
    {
        $q   = trim($request->get('q', ''));
        $loc = $request->get('location', '');

        $query = DB::table('parts_inventory')->where('status', 'Available')->where('stock_qty', '>', 0);
        if ($loc) $query->where('location', $loc);
        if ($q !== '') {
            $query->where(function ($sq) use ($q) {
                $sq->where('part_name', 'like', "%{$q}%")
                   ->orWhere('part_code', 'like', "%{$q}%")
                   ->orWhere('brand', 'like', "%{$q}%");
            });
        }

        $parts = $query->select('id', 'part_code', 'part_name', 'brand', 'model', 'condition_grade',
                                  'location', 'price_local', 'currency_code', 'price_usd', 'stock_qty')
            ->orderBy('part_name')->limit(30)->get();

        return response()->json(['parts' => $parts]);
    }

    // =========================================================
    // POST /admin/invoices/service — store a service/misc receipt.
    // No part_id ever involved, no inventory touched at all — this is
    // the whole point: labor/service charges can't be mistaken for or
    // used to disguise a parts sale.
    // =========================================================
    public function storeService(Request $request)
    {
        $request->validate([
            'customer_name'    => 'required|string|max:120',
            'location'         => 'required|string',
            'items'            => 'required|array|min:1',
            'items.*.item_type'=> 'required|in:service,part',
            'items.*.name'     => 'required_if:items.*.item_type,service|nullable|string',
            'items.*.price'    => 'required|numeric|min:0',
            'items.*.qty'      => 'required|integer|min:1',
            'items.*.part_id'  => 'required_if:items.*.item_type,part|nullable|integer',
        ]);

        $saleLocation = $request->location;
        $currencyCode = self::currencyForLocation($saleLocation)['code'];
        $currency     = self::currencyMeta($currencyCode);
        $businessInfo = $this->getBusinessInfo($saleLocation);

        // ── #16 — Quick Receipt now supports adding real Parts /
        // Consumables alongside Quick Service entries, in one ticket.
        // Parts get the same stock-enforcement as Manual Invoice.
        $stockErrors = [];
        $resolvedParts = [];
        foreach ($request->items as $i => $item) {
            if (($item['item_type'] ?? 'service') === 'part') {
                $part = DB::table('parts_inventory')->where('id', $item['part_id'])->first();
                $qty  = (int) $item['qty'];
                if (!$part) { $stockErrors[] = "A selected part no longer exists."; continue; }
                if ($part->status !== 'Available') { $stockErrors[] = "{$part->part_code} is no longer Available."; continue; }
                if ($qty > $part->stock_qty) { $stockErrors[] = "{$part->part_code}: only {$part->stock_qty} in stock."; continue; }
                $resolvedParts[$i] = $part;
            }
        }
        if (!empty($stockErrors)) {
            return back()->withInput()->with('error', implode(' | ', $stockErrors));
        }

        $lineItems = collect($request->items)->map(function ($item, $i) use ($currencyCode, $resolvedParts) {
            $isPart     = ($item['item_type'] ?? 'service') === 'part';
            $priceLocal = (float) $item['price'];
            $qty        = (int) $item['qty'];
            $lineLocal  = $priceLocal * $qty;
            $part       = $resolvedParts[$i] ?? null;

            return (object)[
                'part_id'          => $part?->id,
                'part_name'        => $isPart ? $part->part_name : $item['name'],
                'part_code'        => $isPart ? $part->part_code : 'SERVICE',
                'brand'            => $isPart ? $part->brand : '',
                'model'            => $isPart ? $part->model : '',
                'year_from'        => $isPart ? $part->year_from : '',
                'year_to'          => $isPart ? $part->year_to : '',
                'engine_code_oem'  => $isPart ? ($part->engine_code_oem ?? '') : '',
                'condition_grade'  => $isPart ? $part->condition_grade : 'N/A',
                'qty'              => $qty,
                'unit_price_local' => $priceLocal,
                'unit_price_fmt'   => self::formatLocal($priceLocal, $currencyCode),
                'total_fmt'        => self::formatLocal($lineLocal, $currencyCode),
                'line_local'       => $lineLocal,
            ];
        });

        $subtotalLocal = $lineItems->sum('line_local');
        $subtotalFmt   = self::formatLocal($subtotalLocal, $currencyCode);
        $invoiceNo     = 'SVC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        $customerInfo = (object)[
            'name'    => $request->customer_name,
            'phone'   => $request->customer_phone ?? '',
            'email'   => $request->customer_email ?? '',
            'address' => $request->customer_address ?? '',
        ];

        $createdAt     = now();
        $paymentMethod = $request->payment_method ?? 'Cash';
        $copyKey       = 'customer';
        $subtotalUsd   = $subtotalLocal; // kept for template compatibility

        DB::beginTransaction();
        try {
            $invoiceId = DB::table('invoices')->insertGetId([
                'invoice_no'        => $invoiceNo,
                'invoice_type'      => 'service', // kept as 'service' even with mixed items — this is still the Quick Receipt flow
                'customer_name'     => $customerInfo->name,
                'customer_phone'    => $customerInfo->phone,
                'customer_email'    => $customerInfo->email,
                'customer_address'  => $customerInfo->address,
                'location'          => $saleLocation,
                'currency_code'     => $currencyCode,
                'subtotal_local'    => $subtotalLocal,
                'subtotal_usd'      => $subtotalLocal, // kept for template compatibility
                'payment_method'    => $paymentMethod,
                'created_by'        => Session::get('staff_name') ?? 'Admin',
                'notes'             => $request->notes ?? null,
                'created_at'        => $createdAt,
                'updated_at'        => $createdAt,
            ]);

            foreach ($lineItems as $li) {
                DB::table('invoice_items')->insert([
                    'invoice_id'       => $invoiceId,
                    'part_id'          => $li->part_id,
                    'part_name'        => $li->part_name,
                    'part_code'        => $li->part_code,
                    'brand'            => $li->brand,
                    'model'            => $li->model,
                    'condition_grade'  => $li->condition_grade,
                    'qty'              => $li->qty,
                    'unit_price_local' => $li->unit_price_local,
                    'unit_price_usd'   => $li->unit_price_local, // kept for template compatibility
                    'created_at'       => $createdAt,
                    'updated_at'       => $createdAt,
                ]);

                // Deduct stock for real parts, same as Manual Invoice
                if ($li->part_id) {
                    $locked = DB::table('parts_inventory')->where('id', $li->part_id)->lockForUpdate()->first();
                    $newQty = max(0, $locked->stock_qty - $li->qty);
                    DB::table('parts_inventory')->where('id', $li->part_id)->update([
                        'stock_qty'  => $newQty,
                        'status'     => $newQty <= 0 ? 'Sold' : 'Available',
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Could not save receipt: ' . $e->getMessage());
        }

        $location = $saleLocation;

        return view('admin.invoices.show', compact(
            'lineItems', 'currency', 'subtotalFmt', 'subtotalUsd',
            'invoiceNo', 'businessInfo', 'saleLocation', 'location',
            'createdAt', 'customerInfo', 'paymentMethod', 'copyKey'
        ));
    }

    // =========================================================
    // GET /admin/invoices — Invoice listing page
    // =========================================================
    public function index(Request $request)
    {
        // ── Manual + service invoices (in-store / phone sales) ──────
        $invoiceRows = DB::table('invoices')
            ->select('id', 'invoice_no as ref', 'customer_name', 'customer_phone',
                     'subtotal_local as amount_local', 'currency_code', 'location',
                     'invoice_type', 'payment_method', 'created_by', 'created_at')
            ->get()
            ->map(fn($r) => (object)[
                'id'            => $r->id,
                'ref'           => $r->ref,
                'customer_name' => $r->customer_name,
                'customer_phone'=> $r->customer_phone,
                'amount_local'  => $r->amount_local,
                'currency_code' => $r->currency_code,
                'location'      => $r->location,
                'channel'       => 'In-Store',
                'type'          => $r->invoice_type ?? 'parts',
                'payment_method'=> $r->payment_method,
                'staff'         => $r->created_by,
                'doc_label'     => 'Receipt', // manual/POS sales are paid at the point of sale by definition
                'created_at'    => $r->created_at,
                'url'           => route('admin.invoices.show.manual', $r->id),
            ]);

        // ── Orders (online checkout + staff-placed walk-in/phone) —
        // channel comes from the orders.channel column itself now, so
        // every sale shows its TRUE origin regardless of which tool
        // created it. All orders populate here unconditionally.
        $orderRows = DB::table('orders')
            ->select('id', 'order_ref as ref', 'customer_name', 'customer_phone',
                     'total_amount_ngn as amount_local', 'customer_country',
                     'payment_method', 'channel', 'payment_status', 'created_at')
            ->get()
            ->map(fn($r) => (object)[
                'id'            => $r->id,
                'ref'           => $r->ref,
                'customer_name' => $r->customer_name,
                'customer_phone'=> $r->customer_phone,
                'amount_local'  => $r->amount_local,
                'currency_code' => 'NGN',
                'location'      => $r->customer_country,
                'channel'       => match($r->channel ?? 'online') {
                    'walk-in' => 'Walk-in',
                    'phone'   => 'Phone',
                    default   => 'Online',
                },
                'type'          => 'order',
                'payment_method'=> $r->payment_method,
                'staff'         => in_array($r->channel ?? 'online', ['walk-in','phone']) ? 'Staff' : 'Customer (online)',
                // Once payment is confirmed, this is now a RECEIPT,
                // not just an invoice — reflects what's actually true:
                // money has changed hands.
                'doc_label'     => in_array($r->payment_status, ['confirmed', 'paid', 'completed']) ? 'Receipt' : 'Invoice',
                'created_at'    => $r->created_at,
                'url'           => route('admin.invoices.show', $r->id),
            ]);

        $all = $invoiceRows->concat($orderRows)->values();

        // ── Search: ref/order#, customer name, phone ──────────────
        if ($q = trim($request->get('q', ''))) {
            $all = $all->filter(function ($r) use ($q) {
                return str_contains(strtolower($r->ref ?? ''), strtolower($q))
                    || str_contains(strtolower($r->customer_name ?? ''), strtolower($q))
                    || str_contains(strtolower($r->customer_phone ?? ''), strtolower($q));
            })->values();
        }

        // ── Date range filter ──────────────────────────────────────
        if ($from = $request->get('date_from')) {
            $all = $all->filter(fn($r) => \Carbon\Carbon::parse($r->created_at)->gte(\Carbon\Carbon::parse($from)->startOfDay()))->values();
        }
        if ($to = $request->get('date_to')) {
            $all = $all->filter(fn($r) => \Carbon\Carbon::parse($r->created_at)->lte(\Carbon\Carbon::parse($to)->endOfDay()))->values();
        }

        // ── Sort ─────────────────────────────────────────────────
        $sort = $request->get('sort', 'date_desc');
        $all = match($sort) {
            'date_asc'    => $all->sortBy('created_at')->values(),
            'name_asc'    => $all->sortBy(fn($r) => strtolower($r->customer_name ?? ''))->values(),
            'name_desc'   => $all->sortByDesc(fn($r) => strtolower($r->customer_name ?? ''))->values(),
            'amount_desc' => $all->sortByDesc('amount_local')->values(),
            'amount_asc'  => $all->sortBy('amount_local')->values(),
            default       => $all->sortByDesc('created_at')->values(), // date_desc
        };

        // Manual pagination since this is a merged in-memory collection
        $perPage = 20;
        $page    = (int) $request->get('page', 1);
        $total   = $all->count();
        $items   = $all->slice(($page - 1) * $perPage, $perPage)->values();

        $invoices = new \Illuminate\Pagination\LengthAwarePaginator(
            $items, $total, $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.invoices.index', compact('invoices'));
    }

    // =========================================================
    // GET /admin/invoices/manual/{id} — Reprint a saved manual invoice
    // =========================================================
    public function showManual(int $id)
    {
        $invoice = DB::table('invoices')->where('id', $id)->first();
        if (!$invoice) abort(404);

        $items = DB::table('invoice_items')
            ->where('invoice_id', $id)
            ->get();

        $currencyCode = $invoice->currency_code ?? self::currencyForLocation($invoice->location)['code'];
        $currency     = self::currencyMeta($currencyCode);
        $businessInfo = $this->getBusinessInfo($invoice->location);

        $lineItems = $items->map(function ($item) use ($currencyCode) {
            $priceLocal = $item->unit_price_local ?? $item->unit_price_usd; // fallback for pre-migration rows
            $lineLocal  = $priceLocal * $item->qty;
            return (object)[
                'part_name'       => $item->part_name,
                'part_code'       => $item->part_code,
                'brand'           => $item->brand,
                'model'           => $item->model,
                'year_from'       => '',
                'year_to'         => '',
                'condition_grade' => $item->condition_grade,
                'engine_code_oem' => '',
                'qty'             => $item->qty,
                'unit_price_usd'  => $priceLocal, // kept for template compatibility
                'unit_price_fmt'  => self::formatLocal($priceLocal, $currencyCode),
                'total_fmt'       => self::formatLocal($lineLocal, $currencyCode),
            ];
        });

        $customerInfo = (object)[
            'name'    => $invoice->customer_name,
            'phone'   => $invoice->customer_phone,
            'email'   => $invoice->customer_email,
            'address' => $invoice->customer_address,
        ];

        $location      = $invoice->location;
        $saleLocation  = $invoice->location;
        $createdAt     = $invoice->created_at;
        $paymentMethod = $invoice->payment_method;
        $copyKey       = 'customer';
        $invoiceNo     = $invoice->invoice_no;
        $subtotalLocal = $invoice->subtotal_local ?? $invoice->subtotal_usd;
        $subtotalUsd   = $subtotalLocal; // kept for template compatibility
        $subtotalFmt   = self::formatLocal($subtotalLocal, $currencyCode);

        return view('admin.invoices.show', compact(
            'invoice', 'lineItems', 'currency', 'subtotalFmt', 'subtotalUsd',
            'invoiceNo', 'businessInfo', 'saleLocation', 'location',
            'createdAt', 'customerInfo', 'paymentMethod', 'copyKey'
        ));
    }
}
