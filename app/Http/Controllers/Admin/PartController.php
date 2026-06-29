<?php
// FILE: app/Http/Controllers/PartController.php
// UPDATED: Fixed-currency pricing (price_local/currency_code, no live
// conversion) + interchange group data (aggregated stock, also-fits
// vehicles) wired in for the detail page.

namespace App\Http\Controllers;

use App\Services\InterchangeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartController extends Controller
{
    private function currencySymbol(string $code): string
    {
        return match ($code) {
            'NGN' => '₦', 'GHS' => 'GH₵', 'GBP' => '£', default => '$',
        };
    }

    private function formatLocal(float $priceLocal, string $currencyCode): string
    {
        $decimals = $currencyCode === 'NGN' ? 0 : 2;
        return $this->currencySymbol($currencyCode) . number_format($priceLocal, $decimals);
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

        $photos = collect(json_decode($part->photos ?? '[]', true))
            ->map(fn($path) => asset(config('media.prefix') . '/' . $path))
            ->values()->all();

        // ── FIXED PRICE — this part's own currency, no conversion ──
        $priceLocal   = $part->price_local ?? $part->price_usd; // fallback for pre-migration rows
        $currencyCode = $part->currency_code ?? 'USD';
        $priceDisplay = $this->formatLocal($priceLocal, $currencyCode);
        $currency     = $currencyCode; // kept for template compatibility

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

        // ── Interchange group — confirmed compatible vehicles +
        // combined stock count across all of them (Phase B3) ──────────
        $interchangeVehicles = collect();
        $aggregatedStock      = null;
        if (!empty($part->interchange_group_id)) {
            $interchange = new InterchangeService();
            $interchangeVehicles = $interchange->vehiclesForGroup($part->interchange_group_id);
            $aggregatedStock     = $interchange->aggregatedStock($part->interchange_group_id);
        }

        // ── Related parts — same category + brand ─────────────
        $related = DB::table('parts_inventory')
            ->where('brand',        $part->brand)
            ->where('part_category', $part->part_category)
            ->where('status',       'Available')
            ->where('id',           '!=', $id)
            ->select('id','part_code','part_name','model','year_from','year_to',
                     'condition_grade','price_local','currency_code','price_usd','location','photos','side')
            ->orderByRaw("FIELD(model, ?) DESC", [$part->model])
            ->orderBy('price_local')
            ->limit(6)
            ->get()
            ->map(function ($p) {
                $ph = json_decode($p->photos ?? '[]', true);
                $p->thumb = $ph[0] ?? null;
                $pLocal = $p->price_local ?? $p->price_usd;
                $pCode  = $p->currency_code ?? 'USD';
                $p->price_display = $this->formatLocal($pLocal, $pCode);
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
        $yearRange = $part->year_from === $part->year_to
            ? $part->year_from
            : "{$part->year_from}–{$part->year_to}";
        $waMsg = urlencode(
            "Hi, I'm enquiring about: {$part->part_name} for {$yearRange} {$part->brand} {$part->model}. " .
            "Part code: {$part->part_code}. Location: {$part->location}. Price: {$priceDisplay}. Is this available?"
        );

        // WhatsApp number by location
        $waNumber = $this->waNumber($part->location);

        return view('parts.show', [
            'part'                 => $part,
            'photos'               => $photos,
            'currency'             => $currency,
            'priceDisplay'         => $priceDisplay,
            'compat'               => $compat,
            'alsoFits'             => $alsoFits,
            'interchangeVehicles'  => $interchangeVehicles,
            'aggregatedStock'      => $aggregatedStock,
            'related'              => $related,
            'donor'                => $donor,
            'inCart'               => $inCart,
            'waMsg'                => $waMsg,
            'waNumber'             => $waNumber,
            'yearRange'            => $yearRange,
        ]);
    }

    // =========================================================
    // Helpers
    // =========================================================
    private function waNumber(string $location): string
    {
        // Nigeria and Ghana locations share one number; all USA
        // locations share the other. A separate WhatsApp-only
        // complaints line (+2348067422777) is shown on the Contact
        // page, not used for per-part enquiries.
        return match(true) {
            str_contains($location, 'Nigeria') ||
            str_contains($location, 'Ghana')   => '2349155688804',
            default                            => '16822563201',
        };
    }
}
