<?php
// FILE: app/Http/Controllers/CartController.php
//
// FIXED-CURRENCY REWRITE — no live FX conversion anywhere. Same
// philosophy as CheckoutController: a part's price_local + currency_code
// (set once at harvest/entry time) is the only real number. The cart
// can hold items from different currencies, but shows them grouped by
// currency rather than blending them into one fake Naira number.

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartController extends Controller
{
    // ── Moniepoint account details (shown to customer) ────────
    const BANK_NAME    = 'Moniepoint MFB';
    const ACCOUNT_NAME = 'Auto Zenith Parts LLC';
    const ACCOUNT_NO   = '8012345678'; // ← replace with real account
    const BANK_CODE    = '50515';      // Moniepoint CBN code

    const CURRENCY_SYMBOLS = ['NGN' => '₦', 'GHS' => 'GH₵', 'USD' => '$'];

    // =========================================================
    // GET /cart  — view cart page
    // =========================================================
    public function index(Request $request)
    {
        $cart  = $this->getCart($request);
        $items = $this->hydrateCartItems($cart);

        // ── Group totals by currency — never blended into one number.
        // Most carts will have exactly one currency in practice (a
        // customer browsing parts is usually shopping one location at
        // a time), but if they somehow have mixed-currency items, show
        // each currency's real subtotal separately rather than
        // inventing a converted blended total.
        $totalsByCurrency = collect($items)
            ->groupBy('currency_code')
            ->map(fn($group) => $group->sum('unit_price_local'));

        $isMixedCurrency = $totalsByCurrency->count() > 1;

        return view('checkout.cart', [
            'items'             => $items,
            'totalsByCurrency'  => $totalsByCurrency,
            'isMixedCurrency'   => $isMixedCurrency,
            'currencySymbols'   => self::CURRENCY_SYMBOLS,
            'bankName'          => self::BANK_NAME,
            'accountName'       => self::ACCOUNT_NAME,
            'accountNo'         => self::ACCOUNT_NO,
        ]);
    }

    // =========================================================
    // POST /cart/add  — add a part to cart (AJAX)
    // =========================================================
    public function add(Request $request)
    {
        $request->validate(['part_id' => 'required|integer|exists:parts_inventory,id']);

        $part = DB::table('parts_inventory')
            ->where('id', $request->part_id)
            ->where('status', 'Available')
            ->select('id','part_code','part_name','brand','model','year_from','year_to',
                     'condition_grade','price_local','price_usd','currency_code','location','photos','stock_qty')
            ->first();

        if (!$part) {
            return response()->json(['error' => 'This part is no longer available.'], 422);
        }

        $cart = $this->getCart($request);
        $items = $cart['items'] ?? [];

        // Check not already in cart
        foreach ($items as $item) {
            if ($item['part_id'] == $part->id) {
                return response()->json([
                    'error' => 'This part is already in your cart.',
                    'count' => count($items),
                ], 422);
            }
        }

        $photos = json_decode($part->photos ?? '[]', true);

        // ── No FX conversion — store the part's own real price, in
        // its own real currency. Never converted to anything else.
        $items[] = [
            'part_id'         => $part->id,
            'part_code'       => $part->part_code,
            'part_name'       => $part->part_name,
            'brand'           => $part->brand,
            'model'           => $part->model,
            'year_from'       => $part->year_from,
            'year_to'         => $part->year_to,
            'condition_grade' => $part->condition_grade,
            'unit_price_local'=> (float) ($part->price_local ?? $part->price_usd ?? 0),
            'currency_code'   => $part->currency_code ?? 'USD',
            'location'        => $part->location,
            'thumb'           => $photos[0] ?? null,
            'quantity'        => 1,
        ];

        $this->saveCart($request, array_merge($cart, ['items' => $items]));

        return response()->json([
            'success' => true,
            'message' => "{$part->part_name} added to cart.",
            'count'   => count($items),
        ]);
    }

    // =========================================================
    // POST /cart/remove  — remove item (AJAX)
    // =========================================================
    public function remove(Request $request)
    {
        $request->validate(['part_id' => 'required|integer']);

        $cart  = $this->getCart($request);
        $items = array_values(array_filter(
            $cart['items'] ?? [],
            fn($i) => $i['part_id'] != $request->part_id
        ));

        $this->saveCart($request, array_merge($cart, ['items' => $items]));

        // Recompute per-currency totals — never blended.
        $totalsByCurrency = collect($items)
            ->groupBy('currency_code')
            ->map(fn($group) => $group->sum('unit_price_local'));

        $formatted = $totalsByCurrency->map(function ($total, $code) {
            $sym = self::CURRENCY_SYMBOLS[$code] ?? '$';
            return $sym . ($code === 'NGN' ? number_format($total) : number_format($total, 2));
        });

        return response()->json([
            'success'           => true,
            'count'             => count($items),
            'totalsByCurrency'  => $formatted,
            // First currency's formatted total, for pages that only
            // show a single running total badge.
            'primaryTotalFmt'   => $formatted->first() ?? ($this::CURRENCY_SYMBOLS['USD'] . '0.00'),
        ]);
    }

    // =========================================================
    // GET /cart/count  — badge count (AJAX)
    // =========================================================
    public function count(Request $request)
    {
        $cart = $this->getCart($request);
        return response()->json(['count' => count($cart['items'] ?? [])]);
    }

    // =========================================================
    // Cart session helpers
    // =========================================================
    private function cartKey(Request $request): string
    {
        $key = $request->cookie('az_cart_key');
        if (!$key) {
            $key = Str::random(40);
        }
        return $key;
    }

    private function getCart(Request $request): array
    {
        $key  = $this->cartKey($request);
        $cart = DB::table('carts')->where('session_key', $key)->first();

        if (!$cart || now()->isAfter($cart->expires_at)) {
            return ['key' => $key, 'items' => []];
        }

        return ['key' => $key, 'items' => json_decode($cart->items, true) ?? []];
    }

    private function saveCart(Request $request, array $cart): void
    {
        $key = $cart['key'];
        DB::table('carts')->updateOrInsert(
            ['session_key' => $key],
            [
                'items'      => json_encode($cart['items']),
                'expires_at' => now()->addDays(7),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        cookie()->queue(cookie('az_cart_key', $key, 60 * 24 * 7)); // 7 days
    }

    // Re-reads each part's REAL current price_local/currency_code from
    // the database (prevents stale cached prices) — no rate, no math.
    private function hydrateCartItems(array $cart): array
    {
        $partIds = array_column($cart['items'] ?? [], 'part_id');
        if (empty($partIds)) return [];

        $live = DB::table('parts_inventory')
            ->whereIn('id', $partIds)
            ->select('id', 'price_local', 'price_usd', 'currency_code')
            ->get()->keyBy('id');

        return array_map(function ($item) use ($live) {
            $part = $live[$item['part_id']] ?? null;
            if ($part) {
                $item['unit_price_local'] = (float) ($part->price_local ?? $part->price_usd ?? 0);
                $item['currency_code']    = $part->currency_code ?? 'USD';
            }
            return $item;
        }, $cart['items'] ?? []);
    }
}
