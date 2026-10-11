<?php
// FILE: app/Http/Controllers/SitemapController.php
//
// /sitemap.xml — tells Google every page worth visiting: the main pages, the policy pages, and every part
// that is available to buy. Cached for an hour so crawlers never slow the site down.

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SitemapController extends Controller
{
    private const MAX_URLS = 50000;   // the sitemap protocol's limit for one file

    public function index()
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $urls = [
                ['loc' => url('/home'),             'changefreq' => 'daily',   'priority' => '1.0'],
                ['loc' => route('parts.search'),    'changefreq' => 'hourly',  'priority' => '0.9'],
                ['loc' => route('other-items'),     'changefreq' => 'daily',   'priority' => '0.6'],
                ['loc' => route('policy.warranty'), 'changefreq' => 'monthly', 'priority' => '0.4'],
                ['loc' => route('policy.refund'),   'changefreq' => 'monthly', 'priority' => '0.3'],
                ['loc' => route('policy.shipping'), 'changefreq' => 'monthly', 'priority' => '0.3'],
            ];

            DB::table('parts_inventory')->where('status', 'Available')
                ->select('id', 'updated_at')->orderByDesc('updated_at')
                ->limit(self::MAX_URLS - count($urls))
                ->get()
                ->each(function ($p) use (&$urls) {
                    $urls[] = [
                        'loc'        => route('parts.show', $p->id),
                        'lastmod'    => $p->updated_at ? \Carbon\Carbon::parse($p->updated_at)->toAtomString() : null,
                        'changefreq' => 'weekly',
                        'priority'   => '0.7',
                    ];
                });

            return self::buildXml($urls);
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** @param array<int, array{loc:string, lastmod?:?string, changefreq?:string, priority?:string}> $urls */
    public static function buildXml(array $urls): string
    {
        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
             . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $out .= "  <url>\n    <loc>" . htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
            if (!empty($u['lastmod']))    $out .= "    <lastmod>{$u['lastmod']}</lastmod>\n";
            if (!empty($u['changefreq'])) $out .= "    <changefreq>{$u['changefreq']}</changefreq>\n";
            if (!empty($u['priority']))   $out .= "    <priority>{$u['priority']}</priority>\n";
            $out .= "  </url>\n";
        }
        return $out . "</urlset>\n";
    }
}
