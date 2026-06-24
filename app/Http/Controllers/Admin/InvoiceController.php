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
                'oi.qty',
                'oi.unit_price_usd',
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
            )->get();

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
            'Oshodi Lagos'     => 'Oshodi Lagos, Nigeria — NGN (₦)',
            'Accra Ghana'      => 'Accra, Ghana — GHS (GH₵)',
        ];

        $staffLocation = Session::get('staff_location');
        $isAdmin       = Session::get('staff_role') === 'admin';

        $currentStaff = DB::table('staff')->where('id', Session::get('staff_id'))->first();
        $staffDiscountCapFixed   = $currentStaff->discount_cap_fixed ?? null;
        $staffDiscountCapPercent = $currentStaff->discount_cap_percent ?? null;

        return view('admin.invoices.manual', compact(
            'parts', 'locations', 'staffLocation', 'isAdmin',
            'staffDiscountCapFixed', 'staffDiscountCapPercent'
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
            'Oshodi Lagos'     => 'Oshodi Lagos, Nigeria — NGN (₦)',
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
        $lineItems = collect($request->items)->map(function ($item) {
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

        // ── Persist invoice + items ──────────────────────────────
        $invoiceId = DB::table('invoices')->insertGetId([
            'invoice_no'                => $invoiceNo,
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
                'created_at'             => $createdAt,
                'updated_at'             => $createdAt,
            ]);
        }
        // ──────────────────────────────────────────────────────────

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
    // GET /admin/invoices — Invoice listing page
    // =========================================================
    public function index()
    {
        $invoices = DB::table('invoices')
            ->orderByDesc('created_at')
            ->paginate(20);

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
