<?php
// FILE: app/Http/Controllers/CheckoutController.php
// Updated: location-based currency, all payment methods for Nigeria and USA

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    // ── Nigerian payment details ───────────────────────────────────
    const NG_BANK_NAME    = 'Moniepoint MFB';
    const NG_ACCOUNT_NAME = 'GASBOK ENGINEERING NIGERIA LIMITED';
    const NG_ACCOUNT_NO   = '5085726530';

    // ── US payment details ─────────────────────────────────────────
    const US_ZELLE_NUMBER  = '5125873425';
    const US_ZELLE_NAME    = 'ACCOLADE AUTOS AND GENERAL LLC';
    const US_CASHAPP       = '$GASBOK';
    const US_VENMO         = '5125873425';

    // ── Office POS locations ───────────────────────────────────────
    const POS_LOCATIONS = [
    'Waxahachie TX'  => '3230 S Hwy 77, Suite 303, Waxahachie TX 75165',
    'Elkhorn WI'     => '613 E Geneva St #23, Elkhorn WI 53121',
    'Ile-Ife Nigeria'=> 'No 1, Suite B, Gasbok Engineering Ave, Ibadan Road, Ile-Ife, Osun State',
    'Ibadan Nigeria' => 'No 11, Zone A, Samadex Junction, Molade, Iwo Road, Ibadan',
    'Lagos Nigeria'  => 'No 3, Aimasiko Street, Shop 1 (Apaku Mall) Mafoluku, Oshodi, Lagos',
];
    // ── WhatsApp numbers ───────────────────────────────────────────
    const BUSINESS_WA_US = '15125873425';
    const BUSINESS_WA_NG = '2349155688804';

    // Determine region from location string
    private function isNigeriaLocation(string $location): bool
    {
        return str_contains($location, 'Nigeria') ||
               str_contains($location, 'Lagos')   ||
               str_contains($location, 'Ghana');
    }

    private function getRates(): array
    {
        return Cache::remember('exchange_rates', now()->addHours(24), function () {
            try {
                $r = \Illuminate\Support\Facades\Http::timeout(5)
                    ->get('https://open.er-api.com/v6/latest/USD')
                    ->json('rates', []);
                return [
                    'NGN' => round($r['NGN'] ?? 1600, 2),
                    'GHS' => round($r['GHS'] ?? 15.5,  2),
                ];
            } catch (\Exception $e) {
                return ['NGN' => 1600, 'GHS' => 15.5];
            }
        });
    }

    // Determine currency from cart items locations
    private function detectCurrency(array $items): string
    {
        foreach ($items as $item) {
            if ($this->isNigeriaLocation($item['location'] ?? '')) {
                return 'NGN';
            }
        }
        return 'USD';
    }

    // =========================================================
    // GET /checkout
    // =========================================================
    public function index(Request $request)
    {
        $cart  = $this->getCart($request);
        $items = $this->hydrateCart($cart);

        if (empty($items)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $rates    = $this->getRates();
        $currency = $this->detectCurrency($items);
        $isNigeria = ($currency === 'NGN');

        $totalUsd = collect($items)->sum(fn($i) => $i['unit_price_usd']);
        $totalNgn = round($totalUsd * $rates['NGN']);

        // Detect dominant location for POS display
        $dominantLocation = $items[0]['location'] ?? 'Waxahachie TX';

        return view('checkout.index', [
            'items'           => $items,
            'totalUsd'        => $totalUsd,
            'totalNgn'        => $totalNgn,
            'currency'        => $currency,
            'isNigeria'       => $isNigeria,
            'rates'           => $rates,
            'posLocations'    => self::POS_LOCATIONS,
            'dominantLocation'=> $dominantLocation,
            // Nigerian payment
            'ngBankName'      => self::NG_BANK_NAME,
            'ngAccountName'   => self::NG_ACCOUNT_NAME,
            'ngAccountNo'     => self::NG_ACCOUNT_NO,
            // US payment
            'usZelleNumber'   => self::US_ZELLE_NUMBER,
            'usZelleName'     => self::US_ZELLE_NAME,
            'usCashApp'       => self::US_CASHAPP,
            'usVenmo'         => self::US_VENMO,
        ]);
    }

    // =========================================================
    // POST /checkout
    // =========================================================
    public function store(Request $request)
    {
        $request->validate([
            'customer_name'     => 'required|string|max:100',
            'customer_phone'    => 'required|string|max:30',
            'customer_email'    => 'nullable|email|max:150',
            'customer_whatsapp' => 'nullable|string|max:30',
            'customer_city'     => 'nullable|string|max:80',
            'customer_country'  => 'required|in:Nigeria,Ghana,USA,Other',
            'payment_method'    => 'required|in:bank_transfer,pos_instore,paystack,zelle,cashapp,venmo,cash',
            'fulfillment_type'  => 'required|in:collection,delivery',
            'delivery_address'  => 'required_if:fulfillment_type,delivery|nullable|string|max:300',
            'notes'             => 'nullable|string|max:500',
        ]);

        $cart  = $this->getCart($request);
        $items = $this->hydrateCart($cart);

        if (empty($items)) {
            return back()->with('error', 'Your cart is empty.');
        }

        $rates     = $this->getRates();
        $currency  = $this->detectCurrency($items);
        $totalUsd  = collect($items)->sum(fn($i) => $i['unit_price_usd']);
        $totalNgn  = round($totalUsd * $rates['NGN']);

        DB::beginTransaction();
        try {
            $lastId = DB::table('orders')->max('id') ?? 0;
            $ref    = 'AZ-' . date('Y') . '-' . str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);

            $orderId = DB::table('orders')->insertGetId([
                'order_ref'        => $ref,
                'customer_name'    => trim($request->customer_name),
                'customer_email'   => $request->customer_email,
                'customer_phone'   => trim($request->customer_phone),
                'customer_whatsapp'=> $request->customer_whatsapp,
                'customer_city'    => $request->customer_city,
                'customer_country' => $request->customer_country,
                'payment_method'   => $request->payment_method,
                'payment_status'   => 'pending',
                'total_amount_ngn' => $totalNgn,
                'total_amount_usd' => $totalUsd,
                'exchange_rate'    => $rates['NGN'],
                'order_status'     => 'awaiting_payment',
                'fulfillment_type' => $request->fulfillment_type,
                'delivery_address' => $request->delivery_address,
                'notes'            => $request->notes,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            foreach ($items as $item) {
                DB::table('order_items')->insert([
                    'order_id'       => $orderId,
                    'part_id'        => $item['part_id'],
                    'part_code'      => $item['part_code'],
                    'part_name'      => $item['part_name'],
                    'brand'          => $item['brand'],
                    'model'          => $item['model'],
                    'year_from'      => $item['year_from'],
                    'year_to'        => $item['year_to'],
                    'condition_grade'=> $item['condition_grade'],
                    'location'       => $item['location'],
                    'quantity'       => 1,
                    'unit_price_usd' => $item['unit_price_usd'],
                    'unit_price_ngn' => round($item['unit_price_usd'] * $rates['NGN']),
                    'subtotal_ngn'   => round($item['unit_price_usd'] * $rates['NGN']),
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                DB::table('parts_inventory')
                    ->where('id', $item['part_id'])
                    ->update(['status' => 'Reserved', 'updated_at' => now()]);
            }

            $this->clearCart($request);
            DB::commit();

            return redirect()->route('checkout.confirmation', ['ref' => $ref]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Something went wrong. Please try again or contact us on WhatsApp.');
        }
    }

    // =========================================================
    // GET /checkout/confirmation/{ref}
    // =========================================================
    public function confirmation(Request $request, string $ref)
    {
        $order = DB::table('orders')->where('order_ref', $ref)->first();
        if (!$order) return redirect()->route('parts.search');

        $items   = DB::table('order_items')->where('order_id', $order->id)->get();
        $isNigeria = in_array($order->customer_country, ['Nigeria','Ghana']);
        $waNumber  = $isNigeria ? self::BUSINESS_WA_NG : self::BUSINESS_WA_US;

        $bankName    = $isNigeria ? self::NG_BANK_NAME    : self::US_ZELLE_NAME;
$accountName = $isNigeria ? self::NG_ACCOUNT_NAME : self::US_ZELLE_NAME;
$accountNo   = $isNigeria ? self::NG_ACCOUNT_NO   : self::US_ZELLE_NUMBER;

return view('checkout.confirmation', [
    'order'          => $order,
    'items'          => $items,
    'isNigeria'      => $isNigeria,
    'waNumber'       => $waNumber,
    'businessWa'     => $waNumber,
    'posLocations'   => self::POS_LOCATIONS,
    'bankName'       => $bankName,
    'accountName'    => $accountName,
    'accountNo'      => $accountNo,
    // Nigerian payment
    'ngBankName'     => self::NG_BANK_NAME,
    'ngAccountName'  => self::NG_ACCOUNT_NAME,
    'ngAccountNo'    => self::NG_ACCOUNT_NO,
    // US payment
    'usZelleNumber'  => self::US_ZELLE_NUMBER,
    'usZelleName'    => self::US_ZELLE_NAME,
    'usCashApp'      => self::US_CASHAPP,
    'usVenmo'        => self::US_VENMO,
]);
    }

    // =========================================================
    // POST /checkout/transfer-proof
    // =========================================================
    public function submitTransferProof(Request $request)
    {
        $request->validate([
            'order_ref'          => 'required|string|exists:orders,order_ref',
            'transfer_reference' => 'required|string|max:100',
        ]);

        DB::table('orders')
            ->where('order_ref', $request->order_ref)
            ->where('payment_status', 'pending')
            ->update([
                'transfer_reference'  => trim($request->transfer_reference),
                'payment_status'      => 'transfer_sent',
                'order_status'        => 'payment_pending_confirmation',
                'transfer_claimed_at' => now(),
                'updated_at'          => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Reference received. We will confirm your payment within 1–2 hours.',
        ]);
    }

    // =========================================================
    // Helpers
    // =========================================================
    private function getCart(Request $request): array
    {
        $key  = $request->cookie('az_cart_key');
        if (!$key) return ['key' => '', 'items' => []];
        $cart = DB::table('carts')->where('session_key', $key)->first();
        if (!$cart || now()->isAfter($cart->expires_at)) return ['key' => $key, 'items' => []];
        return ['key' => $key, 'items' => json_decode($cart->items ?? '[]', true) ?? []];
    }

    private function hydrateCart(array $cart): array
    {
        $partIds = array_column($cart['items'] ?? [], 'part_id');
        if (empty($partIds)) return [];
        $live = DB::table('parts_inventory')
            ->whereIn('id', $partIds)->where('status','Available')
            ->pluck('price_usd','id');
        $rates = $this->getRates();
        return array_filter(array_map(function ($item) use ($live, $rates) {
            if (!isset($live[$item['part_id']])) return null;
            $item['unit_price_usd'] = (float) $live[$item['part_id']];
            $item['unit_price_ngn'] = round($item['unit_price_usd'] * $rates['NGN']);
            return $item;
        }, $cart['items'] ?? []));
    }

    private function clearCart(Request $request): void
    {
        $key = $request->cookie('az_cart_key');
        if ($key) DB::table('carts')->where('session_key', $key)->delete();
        cookie()->queue(cookie()->forget('az_cart_key'));
    }
}
