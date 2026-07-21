<?php
// FILE: app/Http/Controllers/Admin/InvoiceController.php

namespace App\Http\Controllers\Admin;

use App\Events\PartSold;
use App\Http\Controllers\Controller;
use App\Services\LegalTraceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Support\Brands;

class InvoiceController extends Controller
{
    // =========================================================
    // CURRENCY HELPERS
    // =========================================================
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

    public static function currencyMeta(string $code): array
    {
        return match ($code) {
            'NGN' => ['code' => 'NGN', 'symbol' => '₦', 'decimals' => 0],
            'GHS' => ['code' => 'GHS', 'symbol' => 'GH₵', 'decimals' => 2],
            'GBP' => ['code' => 'GBP', 'symbol' => '£', 'decimals' => 2],
            default => ['code' => 'USD', 'symbol' => '$', 'decimals' => 2],
        };
    }

    public static function formatLocal(float $priceLocal, string $currencyCode): string
    {
        $meta = self::currencyMeta($currencyCode);
        return $meta['symbol'] . number_format($priceLocal, $meta['decimals']);
    }

    public static function formatPrice(float $usdPrice, array $currency): string
    {
        $amount = $usdPrice * $currency['rate'];
        return $currency['symbol'] . number_format($amount, $currency['decimals']);
    }

    // =========================================================
    // PAYMENT SUMMARY — static so invoice show blade can call it
    // =========================================================
    public static function invoicePaymentSummary(int $invoiceId): array
    {
        $invoice = DB::table('invoices')->where('id', $invoiceId)->first();
        if (!$invoice) return ['payments' => collect(), 'confirmedPaid' => 0, 'balanceDue' => 0];

        $payments = DB::table('invoice_payments')
            ->where('invoice_id', $invoiceId)
            ->orderBy('created_at')
            ->get();

        $confirmedPaid = $payments->where('status', 'confirmed')->sum('amount_local');
        $subtotal      = $invoice->subtotal_local ?? $invoice->subtotal_usd ?? 0;
        $balanceDue    = max(0, $subtotal - $confirmedPaid);

        return compact('payments', 'confirmedPaid', 'balanceDue');
    }

    // =========================================================
    // BUSINESS INFO BY LOCATION
    // =========================================================
    public function getBusinessInfo(string $location): array
    {
        $loc = strtolower($location);

        if (str_contains($loc, 'oshodi') || str_contains($loc, 'lagos')) {
            return [
                'company'   => 'Gasbok Engineering Nig. Limited',
                'rc'        => 'RC: 1135830',
                'address'   => 'Oshodi, Lagos State, Nigeria',
                'phone'     => '+234 915 568 8804',
                'email'     => 'lagos@autozenithparts.com',
                'bank'      => 'Moniepoint',
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
                'bank'      => 'Moniepoint',
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
    // FOOTER ADDRESSES — printed on every invoice/receipt/transfer-note/
    // waybill, separate from the header's specific-location business
    // info above. Nigeria transactions (any Nigerian location — Ile-Ife,
    // Lagos, Ibadan, Abuja, Akure) show BOTH the Ile-Ife and Lagos
    // Oshodi addresses together, regardless of which one actually made
    // the sale. USA transactions show the Waxahachie address. Ghana
    // currently falls through to the USA address as a placeholder —
    // flag if Ghana should have its own dedicated footer line.
    // =========================================================
    public static function footerAddressesForLocation(string $location): array
    {
        $loc = strtolower($location);

        if (str_contains($loc, 'nigeria') || str_contains($loc, 'ife') || str_contains($loc, 'ibadan')
            || str_contains($loc, 'lagos') || str_contains($loc, 'oshodi') || str_contains($loc, 'abuja')
            || str_contains($loc, 'akure')) {
            return [
                'Auto Zenith Parts Regional HQ — No. 1 Suite B, Gasbok Engineering Avenue, Ibadan Road, Ile-Ife, Osun State, Nigeria',
                'Shop 3 & 4, Oranmiyan Shopping Complex — Lagere, Ile-Ife, Osun State, Nigeria',
                'No. 3 Aimasiko Street, Shop 1 (Apaku Mall) — Mafoluku, Oshodi, Lagos State, Nigeria',
                'No. 11, Zone A Suite 2, Samadex Junction — Molade, Iwo Road, Ibadan, Oyo State, Nigeria',
            ];
        }

        // USA (and Ghana placeholder — see note above)
        return [
            'Accolade Autos and General LLC — 3230 S Hwy 77, Waxahachie TX 75165',
        ];
    }

    // =========================================================
    // COMMISSION HELPER
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
                $monthVolume = (float) DB::table('sales_commissions')
                    ->where('staff_id', $staffId)
                    ->where('currency_code', $currencyCode)
                    ->where('type', 'sale')
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->sum('sale_amount_local');

                $projectedVolume = $monthVolume + $saleAmountLocal;
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

        $orderCurrency = $order->currency_code ?? self::currencyForLocation($order->location ?? 'Waxahachie TX')['code'];

        $items = DB::table('order_items as oi')
            ->leftJoin('parts_inventory as p', 'p.id', '=', 'oi.part_id')
            ->where('oi.order_id', $orderId)
            ->select(
                'oi.item_type',
                'oi.unit_price_local',
                'oi.subtotal_local',
                'oi.unit_price_ngn',
                'oi.unit_price_usd',
                'oi.subtotal_ngn',
                'oi.part_name',
                'oi.part_code',
                'oi.brand',
                'oi.model',
                'oi.year_from',
                'oi.year_to',
                'oi.condition_grade',
                'oi.location as part_location',
                'p.engine_code_oem',
                'p.transmission_code_oem',
                'p.compat_year_from',
                'p.compat_year_to',
                'p.part_category'
            )->get()
            ->map(function ($item) use ($orderCurrency) {
                if (empty($item->unit_price_local)) {
                    $item->unit_price_local = $orderCurrency === 'NGN'
                        ? ($item->unit_price_ngn ?? 0)
                        : ($item->unit_price_usd ?? 0);
                }
                if (empty($item->subtotal_local)) {
                    $item->subtotal_local = $orderCurrency === 'NGN' ? ($item->subtotal_ngn ?? null) : null;
                }
                $item->qty = ($item->subtotal_local && $item->unit_price_local)
                    ? max(1, round($item->subtotal_local / $item->unit_price_local))
                    : 1;
                return $item;
            });

        $saleLocation  = $order->location ?? ($items->first()->part_location ?? 'Waxahachie TX');
        $currencyCode  = $order->currency_code ?? self::currencyForLocation($saleLocation)['code'];
        $businessInfo  = $this->getBusinessInfo($saleLocation);
        $subtotalLocal = $order->total_amount_local ?? $items->sum('subtotal_local');

        $lineItems = $items->map(function ($item) use ($currencyCode) {
            $lineLocal = $item->subtotal_local ?? (($item->unit_price_local ?? 0) * $item->qty);
            return (object) array_merge((array) $item, [
                'unit_price_fmt' => self::formatLocal($item->unit_price_local ?? 0, $currencyCode),
                'total_fmt'      => self::formatLocal($lineLocal, $currencyCode),
            ]);
        });

        $subtotalFmt   = self::formatLocal($subtotalLocal, $currencyCode);
        $invoiceNo     = 'AZP-' . date('Y') . '-' . str_pad($orderId, 5, '0', STR_PAD_LEFT);
        $customerInfo  = (object)[
            'name'    => $order->customer_name ?? '',
            'phone'   => $order->customer_phone ?? '',
            'email'   => $order->customer_email ?? '',
            'address' => $order->customer_address ?? '',
        ];

        $currency      = self::currencyMeta($currencyCode);
        $location      = $saleLocation;
        $createdAt     = $order->created_at ?? now();
        $paymentMethod = $order->payment_method ?? 'Cash';
        $copyKey       = 'customer';
        $subtotalUsd   = $subtotalLocal;
        $invoiceType   = 'order';

        // NEW: orders now have real discount columns (added alongside
        // fixing AdminOrderController::store(), which previously
        // computed a discount in the browser preview but never saved
        // it anywhere). Same gross-subtotal reconstruction and
        // percentage-label approach as showManual(), so orders and
        // manual invoices display discounts identically.
        $discountLocal = (float) ($order->discount_amount_local ?? 0);
        $totalLocal    = $subtotalLocal; // the actual net/charged amount already stored
        $grossSubtotalLocal = $subtotalLocal + $discountLocal;
        $subtotalFmt   = self::formatLocal($grossSubtotalLocal, $currencyCode); // overwrite with the correct GROSS figure
        $totalFmt      = self::formatLocal($totalLocal, $currencyCode);
        $discountFmt   = $discountLocal > 0 ? self::formatLocal($discountLocal, $currencyCode) : null;
        $discountLabel = null;
        if ($discountLocal > 0) {
            $pct = $grossSubtotalLocal > 0 ? ($discountLocal / $grossSubtotalLocal) * 100 : 0;
            $discountLabel = "Discount (" . rtrim(rtrim(number_format($pct, 1), '0'), '.') . "%):";
        }

        // Footer addresses — same logic as showManual().
        $footerAddresses = self::footerAddressesForLocation($saleLocation);

        return view('admin.invoices.show', compact(
            'order', 'lineItems', 'currency', 'subtotalFmt',
            'subtotalUsd', 'invoiceNo', 'businessInfo', 'saleLocation',
            'location', 'createdAt', 'customerInfo', 'paymentMethod', 'copyKey', 'invoiceType',
            'footerAddresses', 'discountLocal', 'discountFmt', 'discountLabel', 'totalFmt'
        ));
    }

    // =========================================================
    // HARVEST INVOICE
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
            $priceLocal = $p->price_local ?? $p->price_usd;
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
                'price_usd'       => $priceLocal,
            ];
        });

        $subtotalLocal = $parts->sum(fn($p) => $p->price_local ?? $p->price_usd);
        $subtotalFmt   = self::formatLocal($subtotalLocal, $currencyCode);
        $subtotalUsd   = $subtotalLocal;
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
            'Waxahachie TX'   => 'Waxahachie TX — USD ($)',
            'Kennedale TX'    => 'Kennedale TX — USD ($)',
            'Elkhorn WI'      => 'Elkhorn WI — USD ($)',
            'Ile-Ife Nigeria' => 'Ile-Ife, Nigeria — NGN (₦)',
            'Ibadan Nigeria'  => 'Ibadan, Nigeria — NGN (₦)',
            'Lagos Nigeria'   => 'Lagos, Nigeria — NGN (₦)',
            'Abuja Nigeria'   => 'Abuja, Nigeria — NGN (₦)',
            'Akure Nigeria'   => 'Akure, Nigeria — NGN (₦)',
            'Accra Ghana'     => 'Accra, Ghana — GHS (GH₵)',
        ];

        $staffLocation = Session::get('staff_location');
        $isAdmin       = Session::get('staff_role') === 'admin';

        $currentStaff = DB::table('staff')->where('id', Session::get('staff_id'))->first();
        $staffDiscountCapFixed   = $currentStaff->discount_cap_fixed ?? null;
        $staffDiscountCapPercent = $currentStaff->discount_cap_percent ?? null;

        $serviceRates = DB::table('service_rates')->where('is_active', true)
            ->orderBy('category')->orderBy('name')->get();

        $servicePricesByLocation = DB::table('service_rate_prices')
            ->get()
            ->groupBy('service_rate_id')
            ->map(fn($rows) => $rows->pluck('price_local', 'location'));

        return view('admin.invoices.manual', compact(
            'parts', 'locations', 'staffLocation', 'isAdmin',
            'staffDiscountCapFixed', 'staffDiscountCapPercent', 'serviceRates', 'servicePricesByLocation'
        ));
    }

    // =========================================================
    // GET /admin/invoices/manual/{id}/edit
    // =========================================================
    public function editManual(int $id)
    {
        // Admin/Manager edit directly. Supervisor may edit but every
        // edit is logged to override_logs for audit — see updateManual()
        // where the override_token is verified before saving.
        if (!in_array(Session::get('staff_role'), ['admin', 'manager', 'supervisor'])) {
            abort(403, 'Only admin, manager, or supervisor accounts can edit invoices.');
        }

        $invoice = DB::table('invoices')->where('id', $id)->first();
        if (!$invoice) abort(404);

        $items = DB::table('invoice_items')->where('invoice_id', $id)->get();

        // Include currently-Available stock AND whatever parts are
        // already linked to THIS invoice (even if their status is now
        // 'Sold' from the original sale) — otherwise the part actually
        // sold on this invoice silently disappears from the picker on
        // edit, making it look like editing forces a fresh inventory
        // pick instead of just adjusting the existing sale.
        $existingPartIds = $items->pluck('part_id')->filter()->values()->toArray();
        $parts = DB::table('parts_inventory')
            ->where(function ($q) use ($existingPartIds) {
                $q->where('status', 'Available');
                if (!empty($existingPartIds)) {
                    $q->orWhereIn('id', $existingPartIds);
                }
            })
            ->orderBy('brand')->orderBy('part_name')
            ->get(['id','part_code','part_name','brand','model',
                   'year_from','year_to','price_local','currency_code','price_usd','condition_grade','location']);

        $locations = [
            'Waxahachie TX'   => 'Waxahachie TX — USD ($)',
            'Kennedale TX'    => 'Kennedale TX — USD ($)',
            'Elkhorn WI'      => 'Elkhorn WI — USD ($)',
            'Ile-Ife Nigeria' => 'Ile-Ife, Nigeria — NGN (₦)',
            'Ibadan Nigeria'  => 'Ibadan, Nigeria — NGN (₦)',
            'Lagos Nigeria'   => 'Lagos, Nigeria — NGN (₦)',
            'Abuja Nigeria'   => 'Abuja, Nigeria — NGN (₦)',
            'Akure Nigeria'   => 'Akure, Nigeria — NGN (₦)',
            'Accra Ghana'     => 'Accra, Ghana — GHS (GH₵)',
        ];

        $staffLocation = Session::get('staff_location');
        $isAdmin       = true;

        $currentStaff = DB::table('staff')->where('id', Session::get('staff_id'))->first();
        $staffDiscountCapFixed   = $currentStaff->discount_cap_fixed ?? null;
        $staffDiscountCapPercent = $currentStaff->discount_cap_percent ?? null;

        $currencyCode = $invoice->currency_code ?? self::currencyForLocation($invoice->location)['code'];
        $currency     = self::currencyMeta($currencyCode);

        $existingItemsJson = $items->map(function ($i) {
            $priceLocal = $i->unit_price_local ?? $i->unit_price_usd;
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
    // PUT /admin/invoices/manual/{id}
    // =========================================================
    public function updateManual(Request $request, int $id)
    {
        $role = Session::get('staff_role');

        if (!in_array($role, ['admin', 'manager', 'supervisor'])) {
            abort(403, 'Only admin, manager, or supervisor accounts can edit invoices.');
        }

        // Supervisor edits require a verified override token (approved via
        // the Supervisor-PIN modal on the edit page) — admin/manager do not.
        if ($role === 'supervisor' && empty($request->override_token)) {
            return back()->withInput()->withErrors([
                'override_token' => 'Supervisor edits require override approval. Please use the Approve button.'
            ]);
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

        $lineItems = collect($request->items)->map(function ($item) {
            $priceLocal     = (float) $item['price'];
            $qty            = (int) $item['qty'];
            $lineGrossLocal = $priceLocal * $qty;
            $discType       = $item['discount_type'] ?? 'fixed';
            $discValue      = (float) ($item['discount_value'] ?? 0);
            $discLocal      = 0;
            if ($discValue > 0) {
                $discLocal = $discType === 'percent'
                    ? $lineGrossLocal * ($discValue / 100)
                    : min($discValue, $lineGrossLocal);
            }
            $lineLocal = $lineGrossLocal - $discLocal;
            // CRITICAL: keep the real part_id if one was submitted. This
            // used to be silently discarded (hardcoded null on insert
            // below), which permanently disconnected every invoice item
            // from its actual inventory part on ANY edit — breaking
            // legal-trace tracking and making an edited invoice
            // indistinguishable from a fresh manual entry. Fixed.
            $partId = !empty($item['part_id']) ? (int) $item['part_id'] : null;
            $part   = $partId ? DB::table('parts_inventory')->find($partId) : null;
            return (object)[
                'part_id'               => $partId,
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

        $newSubtotalLocal   = $subtotalAfterLineDiscounts - $invoiceDiscLocal;
        $totalDiscountLocal = $lineItems->sum('discount_amount_local') + $invoiceDiscLocal;

        $changes = [];
        if ($invoice->customer_name !== $request->customer_name) $changes[] = "Customer name: \"{$invoice->customer_name}\" → \"{$request->customer_name}\"";
        if ($invoice->location !== $saleLocation) $changes[] = "Location: \"{$invoice->location}\" → \"{$saleLocation}\"";
        $oldSubtotalLocal = $invoice->subtotal_local ?? $invoice->subtotal_usd;
        if (round((float) $oldSubtotalLocal, 2) !== round($newSubtotalLocal, 2)) {
            $changes[] = "Subtotal: " . self::formatLocal($oldSubtotalLocal, $invoice->currency_code ?? $currencyCode) . " → " . self::formatLocal($newSubtotalLocal, $currencyCode);
        }
        $oldItemCount = DB::table('invoice_items')->where('invoice_id', $id)->count();
        if ($oldItemCount !== $lineItems->count()) $changes[] = "Item count: {$oldItemCount} → {$lineItems->count()}";
        $changesSummary = $changes ? implode('; ', $changes) : 'No substantive changes detected.';

        DB::table('invoices')->where('id', $id)->update([
            'customer_name'             => $request->customer_name,
            'customer_phone'            => $request->customer_phone ?? '',
            'customer_email'            => $request->customer_email ?? '',
            'customer_address'          => $request->customer_address ?? '',
            'location'                  => $saleLocation,
            'currency_code'             => $currencyCode,
            'subtotal_local'            => $newSubtotalLocal,
            'subtotal_usd'              => $newSubtotalLocal,
            'discount_amount_local'     => $totalDiscountLocal,
            'discount_amount_usd'       => $totalDiscountLocal,
            'discount_type'             => $invoiceDiscValue > 0 ? $invoiceDiscType : null,
            'discount_value'            => $invoiceDiscValue > 0 ? $invoiceDiscValue : null,
            'notes'                     => $request->notes ?? null,
            'updated_at'                => now(),
        ]);

        DB::table('invoice_items')->where('invoice_id', $id)->delete();
        foreach ($lineItems as $li) {
            DB::table('invoice_items')->insert([
                'invoice_id'             => $id,
                'part_id'                => $li->part_id, // FIXED: was hardcoded null, silently unlinking every item from its real inventory part on every edit
                'part_name'              => $li->part_name,
                'part_code'              => $li->part_code,
                'brand'                  => $li->brand,
                'model'                  => $li->model,
                'condition_grade'        => $li->condition_grade,
                'qty'                    => $li->qty,
                'unit_price_local'       => $li->unit_price_local,
                'unit_price_usd'         => $li->unit_price_local,
                'discount_amount_local'  => $li->discount_amount_local,
                'discount_amount_usd'    => $li->discount_amount_local,
                'discount_type'          => $li->discount_type,
                'discount_value'         => $li->discount_value,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        }

        DB::table('invoice_edit_log')->insert([
            'invoice_id'      => $id,
            'edited_by'       => Session::get('staff_name') ?? 'Unknown',
            'staff_role'      => $role,
            'override_by'     => $request->override_token ?? null,
            'changes_summary' => $changesSummary,
            'created_at'      => now(),
            'updated_at'      => now(),
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
            // NEW: optional return credit to apply toward this invoice
            // — e.g. a returned defective part's value going toward a
            // replacement purchase instead of / alongside a cash refund.
            'return_credit_id' => 'nullable|exists:returns,id',
        ]);

        $saleLocation = $request->location;
        $currencyCode = self::currencyForLocation($saleLocation)['code'];
        $currency     = self::currencyMeta($currencyCode);
        $businessInfo = $this->getBusinessInfo($saleLocation);

        $lineItems = collect($request->items)->map(function ($item) use ($currencyCode) {
            $priceLocal     = (float) $item['price'];
            $qty            = (int) $item['qty'];
            $lineGrossLocal = $priceLocal * $qty;
            $discType       = $item['discount_type'] ?? 'fixed';
            $discValue      = (float) ($item['discount_value'] ?? 0);
            $discLocal      = 0;
            if ($discValue > 0) {
                $discLocal = $discType === 'percent'
                    ? $lineGrossLocal * ($discValue / 100)
                    : min($discValue, $lineGrossLocal);
            }
            $lineLocal = $lineGrossLocal - $discLocal;
            $part = !empty($item['part_id']) ? DB::table('parts_inventory')->find($item['part_id']) : null;
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

        $subtotalLocal      = $subtotalAfterLineDiscounts - $invoiceDiscLocal;
        $totalDiscountLocal = $lineItems->sum('discount_amount_local') + $invoiceDiscLocal;

        // ── Apply return credit, if selected ──
        // Re-validated here (not just at the form-validation layer)
        // since the credit's real-time availability could have changed
        // between page load and submit — never trust the amount typed
        // in the form, always re-check against the actual DB row.
        $returnCreditId = null;
        $returnCreditApplied = 0;
        if ($request->return_credit_id) {
            $returnCredit = DB::table('returns')
                ->where('id', $request->return_credit_id)
                ->where('status', 'resolved')
                ->whereNull('credit_applied_at')
                ->first();
            if ($returnCredit && $returnCredit->refund_amount_local > 0) {
                $returnCreditId = $returnCredit->id;
                // Never apply more credit than the invoice is actually
                // worth — cap at whichever is smaller, so a large
                // credit against a small purchase doesn't produce a
                // negative total (any leftover credit simply isn't
                // consumed and remains available for a future invoice).
                $returnCreditApplied = min((float) $returnCredit->refund_amount_local, $subtotalLocal);
                $subtotalLocal -= $returnCreditApplied;
            }
        }

        $subtotalFmt = self::formatLocal($subtotalLocal, $currencyCode);

        $currentStaffForCap          = DB::table('staff')->where('id', Session::get('staff_id'))->first();
        $grossLocal                  = $subtotalAfterLineDiscounts + $lineItems->sum('discount_amount_local');
        $discountPercentOfGross      = $grossLocal > 0 ? ($totalDiscountLocal / $grossLocal) * 100 : 0;
        $exceedsCap = false;
        if ($currentStaffForCap) {
            if ($currentStaffForCap->discount_cap_fixed !== null && $totalDiscountLocal > $currentStaffForCap->discount_cap_fixed) $exceedsCap = true;
            if ($currentStaffForCap->discount_cap_percent !== null && $discountPercentOfGross > $currentStaffForCap->discount_cap_percent) $exceedsCap = true;
        }
        if ($exceedsCap && !$request->filled('discount_override_reason')) {
            return back()->withInput()->with('error', 'This discount exceeds your allowance cap. Please provide an override reason and resubmit.');
        }

        // ── Phase 6: Legal trace enforcement ─────────────────────────
        // If any part in this invoice requires legal documentation
        // (catalytic converters, airbags, engines), a buyer document
        // reference is mandatory before the sale can proceed.
        [$legalOk, $legalErrors] = LegalTraceService::checkCart(
            $lineItems,
            $request->input('buyer_legal_doc')
        );
        if (!$legalOk) {
            return back()->withInput()->with('error', implode(' ', $legalErrors));
        }

        // Stock check
        $stockErrors = [];
        foreach ($request->items as $item) {
            if (empty($item['part_id'])) continue;
            $part = DB::table('parts_inventory')->where('id', $item['part_id'])->first();
            $qty  = (int) ($item['qty'] ?? 1);
            if (!$part) { $stockErrors[] = "Part for \"{$item['name']}\" no longer exists."; continue; }
            if ($part->status !== 'Available') { $stockErrors[] = "{$part->part_code} is no longer Available (status: {$part->status})."; continue; }
            if ($qty > $part->stock_qty) $stockErrors[] = "{$part->part_code}: requested {$qty}, only {$part->stock_qty} in stock.";
        }
        if (!empty($stockErrors)) {
            return back()->withInput()->with('error', 'Cannot complete sale — stock check failed: ' . implode(' | ', $stockErrors));
        }

        $invoiceNo    = 'AZP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
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
        $subtotalUsd   = $subtotalLocal;

        DB::beginTransaction();
        try {
            $invoiceId = DB::table('invoices')->insertGetId([
                'invoice_no'               => $invoiceNo,
                'invoice_type'             => 'parts',
                'customer_name'            => $customerInfo->name,
                'customer_phone'           => $customerInfo->phone,
                'customer_email'           => $customerInfo->email,
                'customer_address'         => $customerInfo->address,
                'location'                 => $saleLocation,
                'currency_code'            => $currencyCode,
                'subtotal_local'           => $subtotalLocal,
                'subtotal_usd'             => $subtotalLocal,
                'discount_amount_local'    => $totalDiscountLocal,
                'discount_amount_usd'      => $totalDiscountLocal,
                'discount_type'            => $invoiceDiscValue > 0 ? $invoiceDiscType : null,
                'discount_value'           => $invoiceDiscValue > 0 ? $invoiceDiscValue : null,
                'discount_override'        => $exceedsCap,
                'discount_override_reason' => $exceedsCap ? $request->discount_override_reason : null,
                'return_credit_id'             => $returnCreditId,
                'return_credit_applied_local'   => $returnCreditApplied,
                'payment_method'           => $paymentMethod,
                'created_by'               => Session::get('staff_name') ?? 'Admin',
                'notes'                    => $request->notes ?? null,
                'created_at'               => $createdAt,
                'updated_at'               => $createdAt,
            ]);

            // Mark the return credit as consumed — prevents it being
            // applied twice to a different invoice later.
            if ($returnCreditId) {
                DB::table('returns')->where('id', $returnCreditId)->update([
                    'credit_applied_at'      => now(),
                    'applied_to_invoice_id'  => $invoiceId,
                    'updated_at'             => now(),
                ]);
            }

            foreach ($lineItems as $li) {
                DB::table('invoice_items')->insert([
                    'invoice_id'            => $invoiceId,
                    'part_id'               => $li->part_id,
                    'part_name'             => $li->part_name,
                    'part_code'             => $li->part_code,
                    'brand'                 => $li->brand,
                    'model'                 => $li->model,
                    'condition_grade'       => $li->condition_grade,
                    'qty'                   => $li->qty,
                    'unit_price_local'      => $li->unit_price_local,
                    'unit_price_usd'        => $li->unit_price_local,
                    'discount_amount_local' => $li->discount_amount_local,
                    'discount_amount_usd'   => $li->discount_amount_local,
                    'discount_type'         => $li->discount_type,
                    'discount_value'        => $li->discount_value,
                    'created_at'            => $createdAt,
                    'updated_at'            => $createdAt,
                ]);

                if ($li->part_id) {
                    $part   = DB::table('parts_inventory')->where('id', $li->part_id)->lockForUpdate()->first();
                    if (!$part || $part->stock_qty < $li->qty) {
                        throw new \Exception("Stock for {$li->part_code} changed before sale completed — only " . ($part->stock_qty ?? 0) . " left.");
                    }
                    $newQty = $part->stock_qty - $li->qty;
                    DB::table('parts_inventory')->where('id', $li->part_id)->update([
                        'stock_qty'  => $newQty,
                        'status'     => $newQty <= 0 ? 'Sold' : 'Available',
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            // ── Phase 4b: record a payment for immediately-paid sales —
            // every Payment Method except "Credit / Deferred" means the
            // customer paid at point of sale.
            //
            // POLICY CHANGE: this used to insert with status='confirmed'
            // immediately, with no staff review step at all — meaning a
            // manual invoice's point-of-sale payment silently became
            // "Paid" the instant the invoice was created, unlike every
            // other payment path in the app (orders, later top-up
            // payments), which all require an explicit staff confirm.
            // Now every payment starts 'pending' regardless of how it
            // was recorded, and only counts toward the confirmed total
            // once a staff member explicitly clicks Confirm — consistent
            // everywhere, no silent auto-approval anywhere.
            if ($paymentMethod !== 'Credit / Deferred') {
                DB::table('invoice_payments')->insert([
                    'invoice_id'             => $invoiceId,
                    'amount_local'           => $subtotalLocal,
                    'payment_method'         => $paymentMethod,
                    'status'                 => 'pending',
                    'notes'                  => 'Recorded at point of sale (manual invoice) — awaiting staff confirmation',
                    'created_at'             => $createdAt,
                    'updated_at'             => $createdAt,
                ]);
            }

            // ── Phase 4: fire PartSold for each real inventory part ──
            // Runs AFTER commit so listener never fires on a rolled-back
            // transaction. Only fires for parts with a real part_id
            // (manual line items never tied to stock are skipped).
            foreach ($lineItems as $li) {
                if ($li->part_id) {
                    PartSold::dispatch(
                        $li->part_id,
                        $invoiceId,
                        $li->line_local,
                        $currencyCode,
                        'invoice'
                    );
                }
            }

            // ── Phase 6: record buyer documentation for legal trace parts ─
            LegalTraceService::recordBuyerDoc(
                $invoiceId,
                'invoice_items',
                $request->input('buyer_legal_doc', '')
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Sale could not be completed: ' . $e->getMessage());
        }

        $this->creditCommissionIfApplicable($invoiceId, $subtotalUsd, $currency['code']);
        $invoiceType = 'parts';

        // ── Notification Idea: "Order confirmation" — fires once the
        // sale is fully committed, using real invoice totals. Skipped
        // silently if the customer has no email/phone on file (walk-in
        // customers with no contact info shouldn't cause an error). ──
        if ($customerInfo->email || $customerInfo->phone) {
            $message = "Hi {$customerInfo->name}, thank you for your purchase! Invoice {$invoiceNo} — Total: {$subtotalFmt}. — Auto Zenith Parts";
            $emailHtml = "<p>Hi {$customerInfo->name},</p>"
                . "<p>Thank you for your purchase! Here's your confirmation:</p>"
                . "<p><strong>Invoice:</strong> {$invoiceNo}<br><strong>Total:</strong> {$subtotalFmt}</p>"
                . "<p>— Auto Zenith Parts</p>";
            \App\Services\NotificationService::notify(
                ['email' => $customerInfo->email, 'phone' => $customerInfo->phone, 'name' => $customerInfo->name],
                "Order Confirmation — Invoice {$invoiceNo}",
                $message,
                $emailHtml
            );
        }

        return view('admin.invoices.show', compact(
            'lineItems', 'currency', 'subtotalFmt', 'subtotalUsd',
            'invoiceNo', 'invoiceId', 'businessInfo', 'saleLocation', 'location',
            'createdAt', 'customerInfo', 'paymentMethod', 'copyKey', 'invoiceType'
        ));
    }

    // =========================================================
    // GET /admin/invoices/service/create
    // =========================================================
    public function createService()
    {
        $serviceRates = DB::table('service_rates')->where('is_active', true)
            ->orderBy('category')->orderBy('name')->get();

        $servicePricesByLocation = DB::table('service_rate_prices')
            ->get()
            ->groupBy('service_rate_id')
            ->map(fn($rows) => $rows->pluck('price_local', 'location'));

        $locations = [
            'Waxahachie TX'   => 'Waxahachie TX — USD ($)',
            'Kennedale TX'    => 'Kennedale TX — USD ($)',
            'Elkhorn WI'      => 'Elkhorn WI — USD ($)',
            'Ile-Ife Nigeria' => 'Ile-Ife, Nigeria — NGN (₦)',
            'Ibadan Nigeria'  => 'Ibadan, Nigeria — NGN (₦)',
            'Lagos Nigeria'   => 'Lagos, Nigeria — NGN (₦)',
            'Abuja Nigeria'   => 'Abuja, Nigeria — NGN (₦)',
            'Akure Nigeria'   => 'Akure, Nigeria — NGN (₦)',
            'Accra Ghana'     => 'Accra, Ghana — GHS (GH₵)',
        ];

        return view('admin.invoices.service', compact('serviceRates', 'locations', 'servicePricesByLocation'));
    }

    // =========================================================
    // AJAX: GET /admin/invoices/service/search-parts
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
    // POST /admin/invoices/service
    // =========================================================
    public function storeService(Request $request)
    {
        $request->validate([
            'customer_name'     => 'required|string|max:120',
            'location'          => 'required|string',
            'items'             => 'required|array|min:1',
            'items.*.item_type' => 'required|in:service,part',
            'items.*.name'      => 'required_if:items.*.item_type,service|nullable|string',
            'items.*.price'     => 'required|numeric|min:0',
            'items.*.qty'       => 'required|integer|min:1',
            'items.*.part_id'   => 'required_if:items.*.item_type,part|nullable|integer',
        ]);

        $saleLocation = $request->location;
        $currencyCode = self::currencyForLocation($saleLocation)['code'];
        $currency     = self::currencyMeta($currencyCode);
        $businessInfo = $this->getBusinessInfo($saleLocation);

        $stockErrors   = [];
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

        // Apply invoice-level discount (Quick Receipt now supports
        // discount, matching manual invoice behavior)
        $discountType  = $request->invoice_discount_type ?? 'fixed';
        $discountValue = (float) ($request->invoice_discount_value ?? 0);
        $discountLocal = 0;
        if ($discountValue > 0) {
            $discountLocal = $discountType === 'percent'
                ? $subtotalLocal * ($discountValue / 100)
                : min($discountValue, $subtotalLocal);
        }
        $totalLocal    = $subtotalLocal - $discountLocal;
        $subtotalFmt   = self::formatLocal($subtotalLocal, $currencyCode);
        $totalFmt      = self::formatLocal($totalLocal, $currencyCode);
        $invoiceNo     = 'SVC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        $customerInfo  = (object)[
            'name'    => $request->customer_name,
            'phone'   => $request->customer_phone ?? '',
            'email'   => $request->customer_email ?? '',
            'address' => $request->customer_address ?? '',
        ];
        $createdAt     = now();
        $paymentMethod = $request->payment_method ?? 'Cash';
        $copyKey       = 'customer';
        $subtotalUsd   = $subtotalLocal;

        DB::beginTransaction();
        try {
            $invoiceId = DB::table('invoices')->insertGetId([
                'invoice_no'       => $invoiceNo,
                'invoice_type'     => 'service',
                'customer_name'    => $customerInfo->name,
                'customer_phone'   => $customerInfo->phone,
                'customer_email'   => $customerInfo->email,
                'customer_address' => $customerInfo->address,
                'location'         => $saleLocation,
                'currency_code'    => $currencyCode,
                'subtotal_local'   => $totalLocal,
                'subtotal_usd'     => $totalLocal,
                'discount_type'    => $discountValue > 0 ? $discountType : null,
                'discount_value'   => $discountValue > 0 ? $discountValue : null,
                'discount_amount_local' => $discountLocal > 0 ? $discountLocal : null,
                'payment_method'   => $paymentMethod,
                'created_by'       => Session::get('staff_name') ?? 'Admin',
                'notes'            => $request->notes ?? null,
                'created_at'       => $createdAt,
                'updated_at'       => $createdAt,
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
                    'unit_price_usd'   => $li->unit_price_local,
                    'created_at'       => $createdAt,
                    'updated_at'       => $createdAt,
                ]);

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

            // ── Phase 4: fire PartSold for real inventory parts ──────
            foreach ($lineItems as $li) {
                if ($li->part_id) {
                    PartSold::dispatch(
                        $li->part_id,
                        $invoiceId,
                        $li->line_local,
                        $currencyCode,
                        'invoice'
                    );
                }
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Could not save receipt: ' . $e->getMessage());
        }

        $location    = $saleLocation;
        $invoiceType = 'service';

        return view('admin.invoices.show', compact(
            'lineItems', 'currency', 'subtotalFmt', 'subtotalUsd', 'totalFmt', 'discountLocal',
            'invoiceNo', 'invoiceId', 'businessInfo', 'saleLocation', 'location',
            'createdAt', 'customerInfo', 'paymentMethod', 'copyKey', 'invoiceType'
        ));
    }

    // =========================================================
    // GET /admin/invoices/car-sale/create
    // =========================================================
    public function createCarSale()
    {
        $locations = [
            'Waxahachie TX'   => 'Waxahachie TX — USD ($)',
            'Kennedale TX'    => 'Kennedale TX — USD ($)',
            'Elkhorn WI'      => 'Elkhorn WI — USD ($)',
            'Ile-Ife Nigeria' => 'Ile-Ife, Nigeria — NGN (₦)',
            'Ibadan Nigeria'  => 'Ibadan, Nigeria — NGN (₦)',
            'Lagos Nigeria'   => 'Lagos, Nigeria — NGN (₦)',
            'Abuja Nigeria'   => 'Abuja, Nigeria — NGN (₦)',
            'Akure Nigeria'   => 'Akure, Nigeria — NGN (₦)',
            'Accra Ghana'     => 'Accra, Ghana — GHS (GH₵)',
        ];

        return view('admin.invoices.car-sale-create', [
            'locations' => $locations,
            // FIXED: was pulling from \App\Support\VehicleBrands::all(),
            // a separate, stale, smaller brand list — never updated when
            // VehicleDatabase got expanded to 21+ brands earlier this
            // session. Now uses the same comprehensive source as
            // everywhere else in the app.
            'brands'    => \App\Data\VehicleDatabase::makes(),
            'years'     => range(date('Y') + 1, 1990),
        ]);
    }

    // =========================================================
    // POST /admin/invoices/car-sale
    // =========================================================
    public function storeCarSale(Request $request)
    {
        $request->validate([
            'customer_name'          => 'required|string|max:120',
            'location'               => 'required|string',
            'vehicles'               => 'required|array|min:1',
            'vehicles.*.brand'       => 'required|string|max:60',
            'vehicles.*.model'       => 'required|string|max:80',
            'vehicles.*.year'        => 'required|integer|min:1990|max:' . (date('Y') + 1),
            // FIXED: VIN was nullable — now required, per the explicit
            // request that every vehicle sale capture a real VIN.
            'vehicles.*.vin'         => 'required|string|size:17',
            'vehicles.*.mileage'     => 'nullable|integer|min:0',
            'vehicles.*.colour'      => 'nullable|string|max:50',
            'vehicles.*.price'       => 'required|numeric|min:0',
            // New: engine details, auto-filled by VIN decode on the
            // form but editable/overridable, matching how every other
            // VIN-decode flow in the app already works.
            'vehicles.*.engine_code' => 'nullable|string|max:60',
            'vehicles.*.engine_l'    => 'nullable|numeric|min:0|max:10',
            'vehicles.*.cylinders'   => 'nullable|string|max:10',
        ]);

        $saleLocation = $request->location;
        $currencyCode = self::currencyForLocation($saleLocation)['code'];
        $businessInfo = $this->getBusinessInfo($saleLocation);

        $lineItems = collect($request->vehicles)->map(function ($v) use ($currencyCode) {
            $priceLocal = (float) $v['price'];
            return (object)[
                'brand'            => $v['brand'],
                'model'            => $v['model'],
                'vehicle_year'     => (int) $v['year'],
                'vin'              => $v['vin'] ?? null,
                'mileage'          => $v['mileage'] ?? null,
                'colour'           => $v['colour'] ?? null,
                'engine_code'      => $v['engine_code'] ?? null,
                'engine_l'         => $v['engine_l'] ?? null,
                'cylinders'        => $v['cylinders'] ?? null,
                'qty'              => 1,
                'unit_price_local' => $priceLocal,
                'unit_price_fmt'   => self::formatLocal($priceLocal, $currencyCode),
                'total_fmt'        => self::formatLocal($priceLocal, $currencyCode),
                'line_local'       => $priceLocal,
            ];
        });

        $subtotalLocal = $lineItems->sum('line_local');
        $subtotalFmt   = self::formatLocal($subtotalLocal, $currencyCode);
        $currency      = self::currencyMeta($currencyCode);
        $invoiceNo     = 'CAR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        $customerInfo  = (object)[
            'name'    => $request->customer_name,
            'phone'   => $request->customer_phone ?? '',
            'email'   => $request->customer_email ?? '',
            'address' => $request->customer_address ?? '',
        ];
        $createdAt     = now();
        $paymentMethod = $request->payment_method ?? 'Cash';
        $copyKey       = 'customer';
        $subtotalUsd   = $subtotalLocal;

        $invoiceId = DB::table('invoices')->insertGetId([
            'invoice_no'       => $invoiceNo,
            'invoice_type'     => 'vehicle',
            'customer_name'    => $customerInfo->name,
            'customer_phone'   => $customerInfo->phone,
            'customer_email'   => $customerInfo->email,
            'customer_address' => $customerInfo->address,
            'location'         => $saleLocation,
            'currency_code'    => $currencyCode,
            'subtotal_local'   => $subtotalLocal,
            'subtotal_usd'     => $subtotalLocal,
            'payment_method'   => $paymentMethod,
            'created_by'       => Session::get('staff_name') ?? 'Admin',
            'notes'            => $request->notes ?? null,
            'created_at'       => $createdAt,
            'updated_at'       => $createdAt,
        ]);

        foreach ($lineItems as $li) {
            DB::table('invoice_items')->insert([
                'invoice_id'       => $invoiceId,
                'part_id'          => null,
                'part_name'        => "{$li->vehicle_year} {$li->brand} {$li->model}",
                'part_code'        => $li->vin ?: 'N/A',
                'brand'            => $li->brand,
                'model'            => $li->model,
                'vin'              => $li->vin,
                'vehicle_year'     => $li->vehicle_year,
                'mileage'          => $li->mileage,
                'colour'           => $li->colour,
                'engine_code'      => $li->engine_code,
                'engine_l'         => $li->engine_l,
                'cylinders'        => $li->cylinders,
                'condition_grade'  => 'N/A',
                'qty'              => 1,
                'unit_price_local' => $li->unit_price_local,
                'unit_price_usd'   => $li->unit_price_local,
                'created_at'       => $createdAt,
                'updated_at'       => $createdAt,
            ]);
        }

        $location    = $saleLocation;
        $invoiceType = 'vehicle';

        // ── Notification Idea: "Order confirmation" — same pattern as
        // storeManual(), for whole-vehicle sales. ──
        if ($customerInfo->email || $customerInfo->phone) {
            $message = "Hi {$customerInfo->name}, thank you for your vehicle purchase! Receipt {$invoiceNo} — Total: {$subtotalFmt}. — Auto Zenith Parts";
            $emailHtml = "<p>Hi {$customerInfo->name},</p>"
                . "<p>Thank you for your vehicle purchase! Here's your confirmation:</p>"
                . "<p><strong>Receipt:</strong> {$invoiceNo}<br><strong>Total:</strong> {$subtotalFmt}</p>"
                . "<p>— Auto Zenith Parts</p>";
            \App\Services\NotificationService::notify(
                ['email' => $customerInfo->email, 'phone' => $customerInfo->phone, 'name' => $customerInfo->name],
                "Order Confirmation — Receipt {$invoiceNo}",
                $message,
                $emailHtml
            );
        }

        return view('admin.invoices.show', compact(
            'lineItems', 'currency', 'subtotalFmt', 'subtotalUsd',
            'invoiceNo', 'invoiceId', 'businessInfo', 'saleLocation', 'location',
            'createdAt', 'customerInfo', 'paymentMethod', 'copyKey', 'invoiceType'
        ));
    }

    // =========================================================
    // GET /admin/invoices — Invoice listing
    // =========================================================
    public function index(Request $request)
    {
        $invoiceRows = DB::table('invoices')
            ->whereNull('deleted_at')
            ->select('id', 'invoice_no as ref', 'customer_name', 'customer_phone',
                     'subtotal_local as amount_local', 'currency_code', 'location',
                     'invoice_type', 'payment_method', 'created_by', 'created_at')
            ->get()
            ->map(fn($r) => (object)[
                'id'             => $r->id,
                'ref'            => $r->ref,
                'customer_name'  => $r->customer_name,
                'customer_phone' => $r->customer_phone,
                'amount_local'   => $r->amount_local,
                'currency_code'  => $r->currency_code,
                'location'       => $r->location,
                'channel'        => 'In-Store',
                'type'           => $r->invoice_type ?? 'parts',
                'payment_method' => $r->payment_method,
                'staff'          => $r->created_by ?? 'Staff',
                'doc_label'      => 'Receipt',
                'created_at'     => $r->created_at,
                'url'            => route('admin.invoices.show.manual', $r->id),
            ]);

        $orderRows = DB::table('orders')
            ->whereNull('deleted_at')
            ->select('id', 'order_ref as ref', 'customer_name', 'customer_phone',
                     'total_amount_local', 'total_amount_ngn', 'total_amount_usd',
                     'currency_code as order_currency_code',
                     'customer_country', 'payment_method', 'channel',
                     'created_by', 'payment_status', 'created_at')
            ->get()
            ->map(fn($r) => (object)[
                'id'             => $r->id,
                'ref'            => $r->ref,
                'customer_name'  => $r->customer_name,
                'customer_phone' => $r->customer_phone,
                'amount_local'   => $r->total_amount_local
                    ?? ($r->order_currency_code === 'NGN' ? $r->total_amount_ngn : $r->total_amount_usd)
                    ?? $r->total_amount_ngn ?? $r->total_amount_usd ?? 0,
                'currency_code'  => $r->order_currency_code ?? ($r->total_amount_ngn ? 'NGN' : 'USD'),
                'location'       => $r->customer_country,
                'channel'        => match($r->channel ?? 'online') {
                    'walk-in' => 'Walk-in',
                    'phone'   => 'Phone',
                    default   => 'Online',
                },
                'type'           => 'order',
                'payment_method' => $r->payment_method,
                'staff'          => $r->created_by ?? (in_array($r->channel ?? 'online', ['walk-in','phone']) ? 'Walk-in sale' : 'Online order'),
                'doc_label'      => in_array($r->payment_status, ['confirmed', 'paid', 'completed']) ? 'Receipt' : 'Invoice',
                'created_at'     => $r->created_at,
                'url'            => route('admin.invoices.show', $r->id),
            ]);

        $all = $invoiceRows->concat($orderRows)->values();

        if ($q = trim($request->get('q', ''))) {
            $all = $all->filter(function ($r) use ($q) {
                return str_contains(strtolower($r->ref ?? ''), strtolower($q))
                    || str_contains(strtolower($r->customer_name ?? ''), strtolower($q))
                    || str_contains(strtolower($r->customer_phone ?? ''), strtolower($q));
            })->values();
        }

        if ($from = $request->get('date_from')) {
            $all = $all->filter(fn($r) => \Carbon\Carbon::parse($r->created_at)->gte(\Carbon\Carbon::parse($from)->startOfDay()))->values();
        }
        if ($to = $request->get('date_to')) {
            $all = $all->filter(fn($r) => \Carbon\Carbon::parse($r->created_at)->lte(\Carbon\Carbon::parse($to)->endOfDay()))->values();
        }

        $sort = $request->get('sort', 'date_desc');
        $all  = match($sort) {
            'date_asc'    => $all->sortBy('created_at')->values(),
            'name_asc'    => $all->sortBy(fn($r) => strtolower($r->customer_name ?? ''))->values(),
            'name_desc'   => $all->sortByDesc(fn($r) => strtolower($r->customer_name ?? ''))->values(),
            'amount_desc' => $all->sortByDesc('amount_local')->values(),
            'amount_asc'  => $all->sortBy('amount_local')->values(),
            default       => $all->sortByDesc('created_at')->values(),
        };

        $perPage  = 20;
        $page     = (int) $request->get('page', 1);
        $total    = $all->count();
        $items    = $all->slice(($page - 1) * $perPage, $perPage)->values();
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

        $items        = DB::table('invoice_items')->where('invoice_id', $id)->get();
        $currencyCode = $invoice->currency_code ?? self::currencyForLocation($invoice->location)['code'];
        $currency     = self::currencyMeta($currencyCode);
        $businessInfo = $this->getBusinessInfo($invoice->location);

        $lineItems = $items->map(function ($item) use ($currencyCode) {
            $priceLocal = $item->unit_price_local ?? $item->unit_price_usd;
            $lineLocal  = $priceLocal * $item->qty;
            return (object)[
                'part_name'             => $item->part_name,
                'part_code'             => $item->part_code,
                'brand'                 => $item->brand,
                'model'                 => $item->model,
                'year_from'             => '',
                'year_to'               => '',
                'condition_grade'       => $item->condition_grade,
                'engine_code_oem'       => '',
                'transmission_code_oem' => '',
                'transmission_code_oem' => '',
                'qty'                   => $item->qty,
                'unit_price_usd'        => $priceLocal,
                'unit_price_fmt'        => self::formatLocal($priceLocal, $currencyCode),
                'total_fmt'             => self::formatLocal($lineLocal, $currencyCode),
                // Per-line discount — was stored on invoice_items all
                // along but never surfaced to the view, so a line that
                // had its own discount (separate from the invoice-wide
                // one shown in the totals box) was invisible on the
                // printed invoice.
                'discount_type'         => $item->discount_type ?? null,
                'discount_value'        => $item->discount_value ?? null,
                'discount_amount_local' => $item->discount_amount_local ?? 0,
            ];
        });

        $customerInfo  = (object)[
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
        $subtotalUsd   = $subtotalLocal;
        $subtotalFmt   = self::formatLocal($subtotalLocal, $currencyCode);
        $invoiceType   = $invoice->invoice_type ?? 'parts';

        // FIXED: discount was computed and saved correctly on create/edit
        // (invoices.discount_amount_local) but never read back out here —
        // the totals block always showed TOTAL == Subtotal with no
        // discount line at all, regardless of what was actually charged.
        //
        // IMPORTANT: invoices.subtotal_local is stored ALREADY NET of
        // discount (see updateManual()/storeManual() — it's the final
        // charged amount despite the column name). So for display:
        //   - the actual charged TOTAL = subtotal_local (as stored)
        //   - the GROSS "Subtotal" line (before discount) = subtotal_local + discount_amount_local
        // Showing stored subtotal_local as both "Subtotal" AND deriving
        // TOTAL from it while also subtracting the discount again would
        // silently short the total by double-counting the discount.
        $discountLocal   = (float) ($invoice->discount_amount_local ?? 0);
        $totalLocal      = $subtotalLocal;                    // the actual stored/charged amount
        $grossSubtotalLocal = $subtotalLocal + $discountLocal + (float) ($invoice->return_credit_applied_local ?? 0); // add both discount AND return credit back for the pre-deduction display line, so Subtotal - Discount - Return Credit = Total exactly
        $subtotalFmt     = self::formatLocal($grossSubtotalLocal, $currencyCode); // overwrite the earlier subtotalFmt with the correct GROSS figure
        $totalFmt        = self::formatLocal($totalLocal, $currencyCode);
        $discountFmt     = $discountLocal > 0 ? self::formatLocal($discountLocal, $currencyCode) : null;
        $discountLabel   = null;
        if ($discountLocal > 0) {
            // FIXED: previously only showed a percentage for discounts
            // originally entered AS a percentage — a fixed-amount
            // discount just said "Discount:" with no indication of what
            // share of the sale that amount actually represents. Now
            // always computes and shows the effective percentage
            // either way, so the math "makes up the sum" transparently
            // regardless of how the discount was entered.
            $effectivePercent = $grossSubtotalLocal > 0 ? ($discountLocal / $grossSubtotalLocal) * 100 : 0;
            $percentStr = rtrim(rtrim(number_format($effectivePercent, 1), '0'), '.');
            $discountLabel = "Discount ({$percentStr}%):";
        }

        // NEW: return credit applied to this invoice, if any — shown
        // as its own line, separate from an ordinary discount, so
        // staff and the customer can both see clearly that part of
        // the total came from a return rather than a promotional
        // discount.
        $returnCreditApplied = (float) ($invoice->return_credit_applied_local ?? 0);
        $returnCreditFmt = $returnCreditApplied > 0 ? self::formatLocal($returnCreditApplied, $currencyCode) : null;

        // Footer business-registration addresses — Nigeria transactions
        // show BOTH the Ile-Ife and Lagos Oshodi addresses regardless of
        // which specific location made the sale; USA transactions show
        // the Waxahachie address. This is separate from $businessInfo
        // above, which still shows the SPECIFIC transacting location's
        // bank/contact details in the header as before.
        $footerAddresses = self::footerAddressesForLocation($saleLocation);

        return view('admin.invoices.show', compact(
            'invoice', 'lineItems', 'currency', 'subtotalFmt', 'subtotalUsd',
            'invoiceNo', 'businessInfo', 'saleLocation', 'location',
            'createdAt', 'customerInfo', 'paymentMethod', 'copyKey', 'invoiceType',
            'discountLocal', 'discountFmt', 'discountLabel', 'totalFmt', 'footerAddresses',
            'returnCreditApplied', 'returnCreditFmt'
        ));
    }

    // =========================================================
    // POST /admin/invoices/{id}/payments
    // =========================================================
    public function addPayment(Request $request, int $invoiceId)
    {
        $request->validate([
            'amount_local'   => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
        ]);

        $invoice = DB::table('invoices')->where('id', $invoiceId)->first();
        if (!$invoice) abort(404);

        $proofPath = null;
        if ($request->hasFile('proof') && $request->file('proof')->isValid()) {
            $proofPath = $request->file('proof')->store('payment-proofs', 'public');
        }

        DB::table('invoice_payments')->insert([
            'invoice_id'     => $invoiceId,
            'amount_local'   => $request->amount_local,
            'payment_method' => $request->payment_method,
            'proof_path'     => $proofPath,
            // FIXED: was inserting 'created_by', a column that doesn't
            // exist on this table (real schema only has
            // confirmed_by_staff_id, populated later when confirmed —
            // this table has no "who submitted it" column at all). Kept
            // the staff name in notes instead so it's not lost entirely.
            'notes'          => trim(($request->notes ?? '') . ' [Added by: ' . (Session::get('staff_name') ?? 'Staff') . ']'),
            'status'         => 'pending',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return back()->with('success', 'Payment recorded as pending. Confirm it to reduce the balance.');
    }

    // =========================================================
    // POST /admin/invoices/{id}/payments/{pid}/confirm
    // =========================================================
    public function confirmPayment(int $invoiceId, int $paymentId)
    {
        DB::table('invoice_payments')
            ->where('id', $paymentId)
            ->where('invoice_id', $invoiceId)
            ->update(['status' => 'confirmed', 'updated_at' => now()]);

        // ── Notification Idea: "Payment received" — fires the moment
        // a payment is actually confirmed, not just recorded pending. ──
        $invoice = DB::table('invoices')->where('id', $invoiceId)->first();
        $payment = DB::table('invoice_payments')->where('id', $paymentId)->first();
        if ($invoice && $payment) {
            $currency   = self::currencyMeta($invoice->currency_code ?? 'NGN');
            $amountFmt  = $currency['symbol'] . number_format($payment->amount_local, $invoice->currency_code === 'NGN' ? 0 : 2);
            $summary    = self::invoicePaymentSummary($invoiceId);
            $balanceFmt = $currency['symbol'] . number_format($summary['balanceDue'], $invoice->currency_code === 'NGN' ? 0 : 2);
            $stillOwed  = $summary['balanceDue'] > 0 ? " Remaining balance: {$balanceFmt}." : ' Invoice is now fully paid — thank you!';

            $message = "Hi {$invoice->customer_name}, we've received your payment of {$amountFmt} for invoice {$invoice->invoice_no}.{$stillOwed} — Auto Zenith Parts";
            $emailHtml = "<p>Hi {$invoice->customer_name},</p>"
                . "<p>We've received your payment of <strong>{$amountFmt}</strong> for invoice <strong>{$invoice->invoice_no}</strong>.</p>"
                . "<p>{$stillOwed}</p><p>— Auto Zenith Parts</p>";

            \App\Services\NotificationService::notify(
                ['email' => $invoice->customer_email, 'phone' => $invoice->customer_phone, 'name' => $invoice->customer_name],
                "Payment Received — Invoice {$invoice->invoice_no}",
                $message,
                $emailHtml
            );
        }

        return back()->with('success', 'Payment confirmed.');
    }

    // =========================================================
    // POST /admin/invoices/{id}/payments/{pid}/reject
    // =========================================================
    public function rejectPayment(int $invoiceId, int $paymentId)
    {
        DB::table('invoice_payments')
            ->where('id', $paymentId)
            ->where('invoice_id', $invoiceId)
            ->update(['status' => 'rejected', 'updated_at' => now()]);

        return back()->with('success', 'Payment rejected.');
    }

    // =========================================================
    // POST /admin/invoices/{id}/send-reminder
    // =========================================================
    public function sendReminder(int $invoiceId)
    {
        $invoice = DB::table('invoices')->where('id', $invoiceId)->first();
        if (!$invoice) abort(404);

        $summary   = self::invoicePaymentSummary($invoiceId);
        $currency  = self::currencyMeta($invoice->currency_code ?? 'NGN');
        $balanceFmt = $currency['symbol'] . number_format($summary['balanceDue'], $invoice->currency_code === 'NGN' ? 0 : 2);

        $message = "Hi {$invoice->customer_name}, this is a reminder that invoice {$invoice->invoice_no} "
            . "has an outstanding balance of {$balanceFmt}. Please reach out to arrange payment. — Auto Zenith Parts";

        $emailHtml = "<p>Hi {$invoice->customer_name},</p>"
            . "<p>This is a reminder that invoice <strong>{$invoice->invoice_no}</strong> has an outstanding balance of <strong>{$balanceFmt}</strong>.</p>"
            . "<p>Please reach out to arrange payment at your earliest convenience.</p>"
            . "<p>— Auto Zenith Parts</p>";

        // Real send via the centralized service — was previously just
        // logging a DB row with no actual delivery attempt at all.
        $result = \App\Services\NotificationService::notify(
            ['email' => $invoice->customer_email, 'phone' => $invoice->customer_phone, 'name' => $invoice->customer_name],
            "Payment Reminder — Invoice {$invoice->invoice_no}",
            $message,
            $emailHtml
        );

        $staffId = Session::get('staff_id');
        DB::table('invoice_payment_reminders')->insert([
            'invoice_id'       => $invoiceId,
            'channel'          => 'email',
            'sent_by_staff_id' => $staffId,
            'created_at'       => now(),
        ]);
        // SMS logged as "attempted" for the record, even though it
        // currently no-ops — keeps the audit trail consistent once
        // SMS is actually turned on later, without a schema change.
        DB::table('invoice_payment_reminders')->insert([
            'invoice_id'       => $invoiceId,
            'channel'          => 'sms',
            'sent_by_staff_id' => $staffId,
            'created_at'       => now(),
        ]);

        $statusMsg = $result['email']
            ? 'Payment reminder emailed.'
            : 'Could not send email (check customer has an email on file).';
        if ($result['whatsapp_link']) {
            $statusMsg .= ' WhatsApp link ready — click "Message customer" to send manually.';
        }

        return back()->with('success', $statusMsg)->with('whatsapp_reminder_link', $result['whatsapp_link']);
    }

    // =========================================================
    // POST /admin/invoices/{id}/send-customer-copy
    // NEW: a real "Send" button for emailing the customer copy of a
    // receipt/invoice directly, distinct from the payment-reminder
    // flow — this is for the ordinary case of just getting the
    // customer their copy, whether or not they're behind on payment.
    // =========================================================
    // =========================================================
    // Shared data builder for the PDF letterhead template — reuses
    // the exact same computation as showManual() (gross subtotal,
    // discount, return credit, footer addresses) so the downloaded
    // PDF and the on-screen invoice never disagree with each other.
    // =========================================================
    private function buildInvoicePdfData(int $invoiceId): ?array
    {
        $invoice = DB::table('invoices')->where('id', $invoiceId)->first();
        if (!$invoice) return null;

        $items = DB::table('invoice_items')->where('invoice_id', $invoiceId)->get();
        $currencyCode = $invoice->currency_code ?? self::currencyForLocation($invoice->location)['code'];
        $currency     = self::currencyMeta($currencyCode);
        $businessInfo = $this->getBusinessInfo($invoice->location);

        $lineItems = $items->map(function ($item) use ($currencyCode) {
            $priceLocal = $item->unit_price_local ?? $item->unit_price_usd;
            $lineLocal  = $priceLocal * $item->qty;
            return (object)[
                'part_name'       => $item->part_name,
                'part_code'       => $item->part_code,
                'brand'           => $item->brand,
                'model'           => $item->model,
                'condition_grade' => $item->condition_grade,
                'qty'             => $item->qty,
                'unit_price_fmt'  => self::formatLocal($priceLocal, $currencyCode),
                'total_fmt'       => self::formatLocal($lineLocal, $currencyCode),
            ];
        });

        $customerInfo = (object)[
            'name' => $invoice->customer_name, 'phone' => $invoice->customer_phone,
            'email' => $invoice->customer_email, 'address' => $invoice->customer_address,
        ];

        $subtotalLocal = $invoice->subtotal_local ?? $invoice->subtotal_usd;
        $discountLocal = (float) ($invoice->discount_amount_local ?? 0);
        $returnCreditApplied = (float) ($invoice->return_credit_applied_local ?? 0);
        $grossSubtotalLocal = $subtotalLocal + $discountLocal + $returnCreditApplied;

        $discountLabel = null;
        if ($discountLocal > 0) {
            $pct = $grossSubtotalLocal > 0 ? ($discountLocal / $grossSubtotalLocal) * 100 : 0;
            $discountLabel = "Discount (" . rtrim(rtrim(number_format($pct, 1), '0'), '.') . "%):";
        }

        return [
            'invoiceNo'    => $invoice->invoice_no,
            'invoice'      => $invoice,
            'lineItems'    => $lineItems,
            'currency'     => $currency,
            'businessInfo' => $businessInfo,
            'saleLocation' => $invoice->location,
            'createdAt'    => $invoice->created_at,
            'customerInfo' => $customerInfo,
            'paymentMethod'=> $invoice->payment_method,
            'isVehicleSale'=> ($invoice->invoice_type ?? 'parts') === 'vehicle',
            'subtotalFmt'  => self::formatLocal($grossSubtotalLocal, $currencyCode),
            'totalFmt'     => self::formatLocal($subtotalLocal, $currencyCode),
            'discountLocal'=> $discountLocal,
            'discountFmt'  => $discountLocal > 0 ? self::formatLocal($discountLocal, $currencyCode) : null,
            'discountLabel'=> $discountLabel,
            'returnCreditApplied' => $returnCreditApplied,
            'returnCreditFmt'     => $returnCreditApplied > 0 ? self::formatLocal($returnCreditApplied, $currencyCode) : null,
            'footerAddresses'     => self::footerAddressesForLocation($invoice->location),
        ];
    }

    // =========================================================
    // GET /admin/invoices/{id}/download-pdf
    // NEW: a real, letterheaded, downloadable PDF — previously
    // "printing an invoice" only meant the browser's print dialog on
    // an HTML page. Requires barryvdh/laravel-dompdf (composer require
    // barryvdh/laravel-dompdf) to be installed.
    // =========================================================
    public function downloadPdf(int $invoiceId)
    {
        $data = $this->buildInvoicePdfData($invoiceId);
        abort_if(!$data, 404);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.invoices.invoice-pdf', $data)->setPaper('a4');
        return $pdf->download("invoice-{$data['invoiceNo']}.pdf");
    }

    // =========================================================
    // POST /admin/invoices/{id}/send-customer-copy
    // A real "Send" button for emailing the customer copy of a
    // receipt/invoice directly. Now attaches the actual letterheaded
    // PDF instead of just plain HTML in the email body.
    // =========================================================
    public function sendCustomerCopy(int $invoiceId)
    {
        $data = $this->buildInvoicePdfData($invoiceId);
        abort_if(!$data, 404);
        $invoice = $data['invoice'];

        // Render the same PDF used for download, as raw bytes to
        // attach directly to the email — no temp file needed.
        $pdfBinary = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.invoices.invoice-pdf', $data)->setPaper('a4')->output();

        $emailHtml = "<p>Hi {$invoice->customer_name},</p>"
            . "<p>Please find attached your receipt for invoice <strong>{$invoice->invoice_no}</strong>.</p>"
            . "<p><strong>Total: {$data['totalFmt']}</strong></p>"
            . "<p>Thank you for your business!</p><p>— Auto Zenith Parts</p>";
        $message = "Hi {$invoice->customer_name}, here's your receipt for invoice {$invoice->invoice_no} — Total: {$data['totalFmt']}. — Auto Zenith Parts";

        $emailSent = false;
        if (!empty($invoice->customer_email)) {
            $emailSent = \App\Services\NotificationService::sendEmail(
                $invoice->customer_email,
                $invoice->customer_name,
                "Your Receipt — Invoice {$invoice->invoice_no}",
                $emailHtml,
                [], // no file-path attachments
                [['data' => $pdfBinary, 'filename' => "invoice-{$invoice->invoice_no}.pdf", 'mime' => 'application/pdf']]
            );
        }
        $whatsappLink = !empty($invoice->customer_phone)
            ? \App\Services\NotificationService::whatsappLink($invoice->customer_phone, $message)
            : null;

        $statusMsg = $emailSent
            ? 'Customer copy emailed with PDF attached.'
            : 'Could not send email (check customer has an email on file).';

        return back()->with('success', $statusMsg)->with('whatsapp_reminder_link', $whatsappLink);
    }

    // =========================================================
    // DELETE /admin/invoices/{id}
    // =========================================================
    public function destroy(Request $request, int $id)
    {
        if (!in_array(Session::get('staff_role'), ['admin', 'manager'])) {
            abort(403);
        }

        DB::table('invoices')->where('id', $id)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        $returnTo = $request->input('return_to', route('admin.invoices.index'));
        return redirect($returnTo)->with('success', 'Invoice moved to recycle bin.');
    }

    // =========================================================
    // POST /admin/invoices/bulk-destroy
    // =========================================================
    public function bulkDestroy(Request $request)
    {
        if (!in_array(Session::get('staff_role'), ['admin', 'manager'])) {
            return response()->json(['success' => false, 'error' => 'Not authorised.'], 403);
        }

        $items = $request->input('items', []);
        if (empty($items)) {
            return response()->json(['success' => false, 'error' => 'No items selected.']);
        }

        $invoiceIds = collect($items)->where('type', 'invoice')->pluck('id')->map('intval')->filter()->values();
        $orderIds   = collect($items)->where('type', 'order')->pluck('id')->map('intval')->filter()->values();

        if ($invoiceIds->isNotEmpty()) {
            DB::table('invoices')->whereIn('id', $invoiceIds)->update(['deleted_at' => now(), 'updated_at' => now()]);
        }
        if ($orderIds->isNotEmpty()) {
            DB::table('orders')->whereIn('id', $orderIds)->update(['deleted_at' => now(), 'updated_at' => now()]);
        }

        return response()->json(['success' => true]);
    }
}
