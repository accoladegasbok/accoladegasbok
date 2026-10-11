<?php
// FILE: app/Support/Seo.php
//
// Search-engine text for a part page: the meta description and the Product data Google reads
// (price, availability, condition, photos, what it fits). Pure functions, no database, so they are easy to test.

namespace App\Support;

class Seo
{
    private const AVAILABILITY = [
        'Available' => 'https://schema.org/InStock',
        'Reserved'  => 'https://schema.org/LimitedAvailability',
        'Sold'      => 'https://schema.org/OutOfStock',
    ];

    /** "2015-2017" or "2015" — uses the confirmed fitment range when there is one. */
    public static function yearRange(object $part): string
    {
        $from = $part->compat_year_from ?? $part->year_from ?? null;
        $to   = $part->compat_year_to   ?? $part->year_to   ?? null;
        if (!$from) return '';
        return ($to && $to != $from) ? "{$from}-{$to}" : (string) $from;
    }

    /** One honest sentence for the search-result snippet (kept under ~155 characters). */
    public static function partDescription(object $part): string
    {
        $years   = self::yearRange($part);
        $fits    = trim($years . ' ' . $part->brand . ' ' . $part->model);
        $grade   = !empty($part->condition_grade) ? ($part->condition_grade === 'New' ? 'New' : "Grade {$part->condition_grade}") : '';
        $parts   = array_filter([
            "Used {$part->part_name} for {$fits}",
            $grade,
            'Tested and warranted',
            'Auto Zenith Parts (USA, Nigeria, Ghana)',
        ]);
        $text = implode('. ', $parts) . '.';
        return mb_strlen($text) > 160 ? rtrim(mb_substr($text, 0, 157), ' .,') . '...' : $text;
    }

    /**
     * schema.org Product for one part.
     * $photos = absolute photo URLs (real photos only). $url = the canonical page address.
     */
    public static function productSchema(object $part, array $photos, string $url): array
    {
        $currency = $part->currency_code ?? 'USD';
        $price    = $part->price_local ?? $part->price_usd ?? null;
        $years    = self::yearRange($part);
        $fits     = trim($years . ' ' . $part->brand . ' ' . $part->model);

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => trim("{$part->part_name} - {$fits}"),
            'description' => self::partDescription($part),
            'sku'         => (string) $part->part_code,
            'brand'       => ['@type' => 'Brand', 'name' => (string) $part->brand],
            'category'    => (string) ($part->part_category ?? ''),
            'url'         => $url,
        ];
        if ($photos) $schema['image'] = array_values($photos);

        if ($price !== null) {
            $schema['offers'] = [
                '@type'           => 'Offer',
                'url'             => $url,
                'price'           => number_format((float) $price, $currency === 'NGN' ? 0 : 2, '.', ''),
                'priceCurrency'   => $currency,
                'availability'    => self::AVAILABILITY[$part->status] ?? 'https://schema.org/OutOfStock',
                'itemCondition'   => ($part->condition_grade ?? '') === 'New' ? 'https://schema.org/NewCondition' : 'https://schema.org/UsedCondition',
                'seller'          => ['@type' => 'Organization', 'name' => 'Auto Zenith Parts'],
            ];
        }
        if ($fits !== '') {
            $schema['isAccessoryOrSparePartFor'] = ['@type' => 'Vehicle', 'name' => $fits];
        }
        return $schema;
    }

    /** JSON safe to place inside a <script type="application/ld+json"> tag. */
    public static function json(array $schema): string
    {
        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
    }
}
