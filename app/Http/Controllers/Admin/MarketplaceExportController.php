<?php
// FILE: app/Http/Controllers/Admin/MarketplaceExportController.php
//
// Generates ready-to-paste listing content (title, description, photo
// links) for a part, formatted per-platform and routed to the right
// region's platforms based on the part's own location. No platform
// here offers a public API for a regular seller to auto-post an
// individual listing (Facebook Marketplace and Jiji.ng have none;
// eBay does — Sell API — and is the one candidate for a REAL
// integration later instead of copy-paste). Amazon is deliberately
// left out of this entirely, per standing decision.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketplaceExportController extends Controller
{
    const USA_LOCATIONS = ['Waxahachie TX', 'Kennedale TX', 'Elkhorn WI'];
    const WEST_AFRICA_LOCATIONS = ['Ile-Ife Nigeria', 'Ibadan Nigeria', 'Lagos Nigeria', 'Abuja Nigeria', 'Akure Nigeria', 'Accra Ghana'];

    const PLATFORMS_BY_REGION = [
        'USA' => [
            ['key' => 'facebook_us', 'label' => 'Facebook Marketplace (US)', 'title_limit' => 100, 'desc_limit' => 9999],
            ['key' => 'ebay',        'label' => 'eBay',                       'title_limit' => 80,  'desc_limit' => 9999],
        ],
        'West Africa' => [
            ['key' => 'facebook_ng', 'label' => 'Facebook Marketplace (Nigeria)', 'title_limit' => 100, 'desc_limit' => 9999],
            ['key' => 'jiji_ng',     'label' => 'Jiji.ng',                        'title_limit' => 100, 'desc_limit' => 9999],
        ],
    ];

    // =========================================================
    // GET /admin/inventory/{id}/marketplace-export
    // =========================================================
    public function show(int $id)
    {
        $part = DB::table('parts_inventory')->where('id', $id)->first();
        abort_if(!$part, 404);

        $region    = $this->regionForLocation($part->location);
        $platforms = self::PLATFORMS_BY_REGION[$region] ?? self::PLATFORMS_BY_REGION['USA'];

        $vehicleStr = trim(
            ($part->brand ?? '') . ' ' . ($part->model ?? '') . ' ' .
            ($part->year_from ?? '') . ($part->year_to && $part->year_to != $part->year_from ? '-' . $part->year_to : '')
        );

        $sym   = ['NGN' => '₦', 'GHS' => 'GH₵', 'USD' => '$'][$part->currency_code ?? 'USD'] ?? '$';
        $price = $sym . (($part->currency_code ?? 'USD') === 'NGN' ? number_format($part->price_local) : number_format($part->price_local, 2));

        $photos = collect(json_decode($part->photos ?? '[]', true))
            ->map(fn($p) => asset('storage/' . $p))
            ->values();

        $permalink = route('parts.show', $part->id);

        // Full-length content — platform-specific truncation happens
        // in the view where the person can see exactly what got cut.
        $title = $this->buildTitle($part, $vehicleStr);
        $description = $this->buildDescription($part, $vehicleStr, $price, $permalink);

        return view('admin.inventory.marketplace-export', [
            'part'       => $part,
            'region'     => $region,
            'platforms'  => $platforms,
            'vehicleStr' => $vehicleStr,
            'price'      => $price,
            'photos'     => $photos,
            'permalink'  => $permalink,
            'title'      => $title,
            'description'=> $description,
        ]);
    }

    private function regionForLocation(?string $location): string
    {
        if (in_array($location, self::USA_LOCATIONS, true)) return 'USA';
        if (in_array($location, self::WEST_AFRICA_LOCATIONS, true)) return 'West Africa';
        // Unknown/new location — default to USA rather than silently
        // producing an empty platform list.
        return 'USA';
    }

    private function buildTitle($part, string $vehicleStr): string
    {
        $grade = $part->condition_grade ? " - Grade {$part->condition_grade}" : '';
        return trim("{$vehicleStr} {$part->part_name}{$grade}");
    }

    private function buildDescription($part, string $vehicleStr, string $price, string $permalink): string
    {
        $lines = [];
        $lines[] = "✅ {$part->part_name} — {$vehicleStr}";

        if ($part->condition_grade) {
            $gradeDesc = match ($part->condition_grade) {
                'A' => 'Like New',
                'B' => 'Good',
                'C' => 'Fair',
                'New' => 'New OEM',
                default => $part->condition_grade,
            };
            $lines[] = "🔧 Condition: Grade {$part->condition_grade} ({$gradeDesc})";
        }

        if ($part->engine_code_oem) {
            $disp = $part->engine_displacement ? " ({$part->engine_displacement})" : '';
            $lines[] = "⚙️ Engine Code: {$part->engine_code_oem}{$disp}";
        }
        if ($part->drive_type) {
            $lines[] = "🚗 Drive Type: {$part->drive_type}";
        }
        if ($part->mileage) {
            $lines[] = "📏 Donor Vehicle Mileage: " . number_format($part->mileage) . " miles";
        }

        // Same simple warranty rule already used elsewhere (product
        // info editor) — 90 days for Engine/Transmission, 30 for
        // everything else.
        $warrantyDays = in_array($part->part_category, ['Engine', 'Transmission']) ? 90 : 30;
        $lines[] = "🛡️ Warranty: {$warrantyDays} Days";

        $lines[] = "💰 Price: {$price}";
        $lines[] = "📍 Location: {$part->location}";
        $lines[] = "";
        $lines[] = "📞 Message us here or WhatsApp/call for fastest response.";
        $lines[] = "🔗 Full details & more photos: {$permalink}";
        $lines[] = "";
        $lines[] = "AUTO ZENITH PARTS — Quality Used Auto Parts";

        return implode("\n", $lines);
    }
}
