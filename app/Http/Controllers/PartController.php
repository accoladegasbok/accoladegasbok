<?php
// FILE: app/Http/Controllers/PartController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PartController extends Controller
{
    // ── Currency rates ────────────────────────────────────────
    private function rates(): array
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

    // =========================================================
    // GET /parts/{id}  — Part detail page
    // =========================================================
    public function show(Request $request, int $id)
    {
        // ── Fetch the part ────────────────────────────────────
        $part = DB::table('parts_inventory')
            ->where('id', $id)
            ->first();

        if (!$part) {
            abort(404, 'Part not found.');
        }

        $photos  = json_decode($part->photos ?? '[]', true);
        $rates   = $this->rates();
        $currency = $request->get('currency', 'USD');

        // ── Compatibility data from parts_compatibility table ──
        $compat = DB::table('parts_compatibility')
            ->where('brand', $part->brand)
            ->where(function ($q) use ($part) {
                $q->where('model', 'like', '%'.$part->model.'%')
                  ->orWhere('model', 'All Models');
            })
            ->where(function ($q) use ($part) {
                $q->where('part_category', $part->part_category)
                  ->orWhere('part_subcategory', 'like', '%'.$part->part_name.'%');
            })
            ->where('year_from', '<=', $part->year_to)
            ->where('year_to',   '>=', $part->year_from)
            ->first();

        $alsoFits = [];
        if ($compat && $compat->also_fits) {
            $alsoFits = json_decode($compat->also_fits, true) ?? [];
        }

        // ── Related parts — same category + brand ─────────────
        $related = DB::table('parts_inventory')
            ->where('brand',        $part->brand)
            ->where('part_category', $part->part_category)
            ->where('status',       'Available')
            ->where('id',           '!=', $id)
            ->select('id','part_code','part_name','model','year_from','year_to',
                     'condition_grade','price_usd','location','photos','side')
            ->orderByRaw("FIELD(model, ?) DESC", [$part->model])
            ->orderBy('price_usd')
            ->limit(6)
            ->get()
            ->map(function ($p) use ($rates, $currency) {
                $ph = json_decode($p->photos ?? '[]', true);
                $p->thumb = $ph[0] ?? null;
                $p->price_display = $this->formatPrice($p->price_usd, $currency, $rates);
                return $p;
            });

        // ── Donor vehicle info ─────────────────────────────────
        $donor = null;
        if ($part->donor_vin) {
            $donor = DB::table('donor_vehicles')
                ->where('vin', $part->donor_vin)
                ->first();
        }

        // ── Already in cart check (cookie-based) ──────────────
        $cartKey  = $request->cookie('az_cart_key');
        $inCart   = false;
        if ($cartKey) {
            $cart = DB::table('carts')->where('session_key', $cartKey)->first();
            if ($cart) {
                $cartItems = json_decode($cart->items ?? '[]', true);
                $inCart = collect($cartItems)->contains('part_id', $id);
            }
        }

        // ── Whatsapp pre-filled msg ───────────────────────────
        $yearRange    = $part->year_from === $part->year_to
            ? $part->year_from
            : "{$part->year_from}–{$part->year_to}";
        $priceDisplay = $this->formatPrice($part->price_usd, $currency, $rates);
        $waMsg = urlencode(
            "Hi, I'm enquiring about: {$part->part_name} for {$yearRange} {$part->brand} {$part->model}. " .
            "Part code: {$part->part_code}. Location: {$part->location}. Price: {$priceDisplay}. Is this available?"
        );

        // WhatsApp number by location
        $waNumber = $this->waNumber($part->location);

        return view('parts.show', [
            'part'        => $part,
            'photos'      => $photos,
            'rates'       => $rates,
            'currency'    => $currency,
            'priceDisplay'=> $priceDisplay,
            'compat'      => $compat,
            'alsoFits'    => $alsoFits,
            'related'     => $related,
            'donor'       => $donor,
            'inCart'      => $inCart,
            'waMsg'       => $waMsg,
            'waNumber'    => $waNumber,
            'yearRange'   => $yearRange,
        ]);
    }

    // =========================================================
    // Helpers
    // =========================================================
    private function formatPrice(float $usd, string $currency, array $rates): string
    {
        return match($currency) {
            'NGN'   => '₦' . number_format(round($usd * $rates['NGN'])),
            'GHS'   => 'GH₵' . number_format($usd * $rates['GHS'], 2),
            default => '$' . number_format($usd, 2),
        };
    }

    private function waNumber(string $location): string
    {
        return match(true) {
            str_contains($location, 'Nigeria') ||
            str_contains($location, 'Lagos')   => '2347064413764',
            str_contains($location, 'Ghana')   => '2349155688804',
            default                            => '15125873425',
        };
    }
}
