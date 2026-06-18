<?php
// FILE: app/Http/Controllers/CartController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CartController extends Controller
{
    // ── Moniepoint account details (shown to customer) ────────
    const BANK_NAME    = 'Moniepoint MFB';
    const ACCOUNT_NAME = 'Auto Zenith Parts LLC';
    const ACCOUNT_NO   = '8012345678'; // ← replace with real account
    const BANK_CODE    = '50515';      // Moniepoint CBN code

    // ── Exchange rate fallback ─────────────────────────────────
    private function getNgnRate(): float
    {
        return Cache::get('exchange_rates.NGN', 1600.0);
    }

    // =========================================================
    // GET /cart  — view cart page
    // =========================================================
    public function index(Request $request)
    {
        $cart  = $this->getCart($request);
        $items = $this->hydrateCartItems($cart);
        $rate  = $this->getNgnRate();

        $totalUsd = collect($items)->sum(fn($i) => $i['unit_price_usd'] * $i['quantity']);
        $totalNgn = round($totalUsd * $rate);

        return view('checkout.cart', [
            'items'    => $items,
            'totalUsd' => $totalUsd,
            'totalNgn' => $totalNgn,
            'rate'     => $rate,
            'bankName'    => self::BANK_NAME,
            'accountName' => self::ACCOUNT_NAME,
            'accountNo'   => self::ACCOUNT_NO,
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
                     'condition_grade','price_usd','location','photos','stock_qty')
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

        $rate   = $this->getNgnRate();
        $photos = json_decode($part->photos ?? '[]', true);

        $items[] = [
            'part_id'        => $part->id,
            'part_code'      => $part->part_code,
            'part_name'      => $part->part_name,
            'brand'          => $part->brand,
            'model'          => $part->model,
            'year_from'      => $part->year_from,
            'year_to'        => $part->year_to,
            'condition_grade'=> $part->condition_grade,
            'unit_price_usd' => (float) $part->price_usd,
            'unit_price_ngn' => round($part->price_usd * $rate),
            'location'       => $part->location,
            'thumb'          => $photos[0] ?? null,
            'quantity'       => 1,
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

        $rate     = $this->getNgnRate();
        $totalNgn = round(collect($items)->sum(fn($i) => $i['unit_price_usd']) * $rate);

        return response()->json([
            'success'  => true,
            'count'    => count($items),
            'totalNgn' => number_format($totalNgn),
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

    private function hydrateCartItems(array $cart): array
    {
        // Re-validate prices from DB (prevent stale cached prices)
        $partIds = array_column($cart['items'] ?? [], 'part_id');
        if (empty($partIds)) return [];

        $live = DB::table('parts_inventory')
            ->whereIn('id', $partIds)
            ->pluck('price_usd', 'id');

        $rate = $this->getNgnRate();

        return array_map(function ($item) use ($live, $rate) {
            $livePrice = $live[$item['part_id']] ?? $item['unit_price_usd'];
            $item['unit_price_usd'] = (float) $livePrice;
            $item['unit_price_ngn'] = round($livePrice * $rate);
            return $item;
        }, $cart['items'] ?? []);
    }
}
