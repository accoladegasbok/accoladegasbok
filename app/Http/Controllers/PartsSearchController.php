<?php
// FILE: app/Http/Controllers/PartsSearchController.php
// Updated: now uses OemDatabase class for OEM code lookups

namespace App\Http\Controllers;

use App\Data\VehicleDatabase;
use App\Data\OemDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;

class PartsSearchController extends Controller
{
    public function getCurrencyRates(): array
    {
        return Cache::remember('exchange_rates', now()->addHours(24), function () {
            try {
                $r = Http::timeout(5)
                    ->get('https://open.er-api.com/v6/latest/USD')
                    ->json('rates', []);
                return [
                    'NGN' => round($r['NGN'] ?? 1600, 2),
                    'GHS' => round($r['GHS'] ?? 15.5, 2),
                ];
            } catch (\Exception $e) {
                return ['NGN' => 1600, 'GHS' => 15.5];
            }
        });
    }

    private function locationCurrency(string $location): string
    {
        if (str_contains($location, 'Ghana')) return 'GHS';
        if (str_contains(strtolower($location), 'nigeria') ||
            str_contains(strtolower($location), 'lagos')) return 'NGN';
        return 'USD';
    }

    public function index(Request $request)
    {
        $makes      = VehicleDatabase::makes();
        $years      = VehicleDatabase::years();
        $categories = [
            'Engine','Transmission','Body','Suspension','Electrical',
            'Interior','Cooling','Brakes','Airbag','Fuel','Exhaust','Seat','Wheels','Consumable',
        ];

        $filters = $this->buildFilters($request);
        $parts   = $this->searchParts($filters);
        $rates   = $this->getCurrencyRates();
        $currency = $request->get('currency', 'USD');

        $totalAvailable = DB::table('parts_inventory')
            ->where('status', 'Available')
            ->count();

        return view('parts.search', compact(
            'makes','years','categories','parts','rates','currency',
            'filters','totalAvailable'
        ))->with([
            'total'     => $parts->total(),
            'locations' => [
                'Waxahachie TX'   => 'Waxahachie TX 🇺🇸',
                'Elkhorn WI'      => 'Elkhorn WI 🇺🇸',
                'Ile-Ife Nigeria' => 'Ile-Ife Nigeria 🇳🇬',
                'Ibadan Nigeria'  => 'Ibadan Nigeria 🇳🇬',
                'Lagos Nigeria'   => 'Lagos Nigeria 🇳🇬',
                'Accra Ghana'     => 'Accra Ghana 🇬🇭',
            ],
        ]);
    }

    public function modelsByMake(Request $request): \Illuminate\Http\JsonResponse
    {
        $make   = strtoupper(trim($request->get('make', '')));
        $models = VehicleDatabase::modelsForMake($make);

        $inventoryModels = DB::table('parts_inventory')
            ->where('brand', 'like', "%{$make}%")
            ->where('status', 'Available')
            ->distinct()
            ->pluck('model')
            ->map(fn($m) => strtoupper($m))
            ->toArray();

        $merged = collect(array_merge($models, $inventoryModels))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        return response()->json(['models' => $merged]);
    }

    public function vinDecode(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['vin' => 'required|string|size:17']);
        $vin = strtoupper(trim($request->vin));

        $data = Cache::remember("nhtsa_{$vin}", now()->addDays(30), function () use ($vin) {
            try {
                $res = Http::timeout(8)
                    ->get("https://vpic.nhtsa.dot.gov/api/vehicles/DecodeVin/{$vin}?format=json");
                if ($res->failed()) return null;

                $r = collect($res->json('Results', []));
                return [
                    'make'         => $r->firstWhere('Variable', 'Make')['Value']                       ?? null,
                    'model'        => $r->firstWhere('Variable', 'Model')['Value']                      ?? null,
                    'year'         => $r->firstWhere('Variable', 'Model Year')['Value']                 ?? null,
                    'trim'         => $r->firstWhere('Variable', 'Trim')['Value']                       ?? null,
                    'body_style'   => $r->firstWhere('Variable', 'Body Class')['Value']                 ?? null,
                    'engine_l'     => $r->firstWhere('Variable', 'Displacement (L)')['Value']           ?? null,
                    'engine_cyl'   => $r->firstWhere('Variable', 'Engine Number of Cylinders')['Value'] ?? null,
                    'drive_type'   => $r->firstWhere('Variable', 'Drive Type')['Value']                 ?? null,
                    'origin'       => $r->firstWhere('Variable', 'Plant Country')['Value']              ?? null,
                    'fuel_type'    => $r->firstWhere('Variable', 'Fuel Type - Primary')['Value']        ?? null,
                    'transmission' => $r->firstWhere('Variable', 'Transmission Style')['Value']         ?? null,
                    'trans_speeds' => $r->firstWhere('Variable', 'Transmission Speeds')['Value']        ?? null,
                ];
            } catch (\Exception $e) {
                return null;
            }
        });

        if (!$data || !$data['make']) {
            return response()->json(['error' => 'Could not decode this VIN. Please check and try again.'], 422);
        }

        // Use OemDatabase for accurate OEM code lookup
        $oem = OemDatabase::lookup(
            $data['make']      ?? '',
            $data['model']     ?? '',
            (int)($data['year']      ?? 0),
            (int)($data['engine_cyl'] ?? 0),
            (float)($data['engine_l'] ?? 0)
        );

        $data['oem_engine_code']       = $oem['engine_code'];
        $data['oem_transmission_code'] = $oem['transmission_code'];
        $data['pin_count']             = $oem['pin_count'];
        $data['gear_alias']            = $oem['gear_alias'];
        $data['market_note']           = $oem['market_note'] ?? null;

        return response()->json(['vehicle' => $data]);
    }

    public function ajaxSearch(Request $request): \Illuminate\Http\JsonResponse
    {
        $filters = $this->buildFilters($request);
        $parts   = $this->searchParts($filters);
        $rates   = $this->getCurrencyRates();

        return response()->json([
            'parts' => $parts->map(fn($p) => $this->formatPart($p, $rates)),
            'total' => $parts->total(),
            'pages' => $parts->lastPage(),
        ]);
    }

    private function buildFilters(Request $request): array
    {
        return [
            'make'      => trim($request->get('make', '')),
            'model'     => trim($request->get('model', '')),
            'year'      => trim($request->get('year', '')),
            'category'  => trim($request->get('category', '')),
            'q'         => trim($request->get('q', '')),
            'location'  => trim($request->get('location', '')),
            'currency'  => $request->get('currency', 'USD'),
            'condition' => trim($request->get('condition', '')),
            'price_min' => trim($request->get('price_min', '')),
            'price_max' => trim($request->get('price_max', '')),
            'sort'      => trim($request->get('sort', 'newest')),
            'page'      => (int) $request->get('page', 1),
        ];
    }

    private function searchParts(array $filters)
    {
        $q = DB::table('parts_inventory')
            ->where('status', 'Available')
            ->orderByDesc('created_at');

        if ($filters['make'])     $q->where('brand', 'like', '%'.$filters['make'].'%');
        if ($filters['model'])    $q->where('model', 'like', '%'.$filters['model'].'%');
        if ($filters['category']) $q->where('part_category', $filters['category']);
        if ($filters['location']) $q->where('location', $filters['location']);
        if ($filters['condition'])$q->where('condition_grade', $filters['condition']);
        if ($filters['price_min'])$q->where('price_usd', '>=', $filters['price_min']);
        if ($filters['price_max'])$q->where('price_usd', '<=', $filters['price_max']);

        if ($filters['year']) {
            $q->where('year_from', '<=', $filters['year'])
              ->where('year_to',   '>=', $filters['year']);
        }

        if ($filters['q']) {
            $kw = $filters['q'];
            $q->where(function($sq) use ($kw) {
                $sq->where('part_name',              'like', "%{$kw}%")
                   ->orWhere('part_code',             'like', "%{$kw}%")
                   ->orWhere('model',                 'like', "%{$kw}%")
                   ->orWhere('oem_part_number',       'like', "%{$kw}%")
                   ->orWhere('engine_code_oem',       'like', "%{$kw}%")
                   ->orWhere('transmission_code_oem', 'like', "%{$kw}%")
                   ->orWhere('gear_alias',            'like', "%{$kw}%")
                   ->orWhere('description',           'like', "%{$kw}%");
            });
        }

        if ($filters['sort'] === 'price_asc')  $q->reorder('price_usd', 'asc');
        if ($filters['sort'] === 'price_desc') $q->reorder('price_usd', 'desc');
        if ($filters['sort'] === 'mileage')    $q->reorder('mileage',   'asc');

        return $q->paginate(24);
    }

    private function formatPart(object $p, array $rates): array
    {
        $photos = json_decode($p->photos ?? '[]', true);
        $cur    = $this->locationCurrency($p->location);
        $price  = match($cur) {
            'NGN'   => '₦' . number_format(round($p->price_usd * $rates['NGN'])),
            'GHS'   => 'GH₵' . number_format($p->price_usd * $rates['GHS'], 2),
            default => '$' . number_format($p->price_usd, 2),
        };

        return [
            'id'                    => $p->id,
            'part_code'             => $p->part_code,
            'part_name'             => $p->part_name,
            'brand'                 => $p->brand,
            'model'                 => $p->model,
            'year_from'             => $p->year_from,
            'year_to'               => $p->year_to,
            'part_category'         => $p->part_category,
            'condition_grade'       => $p->condition_grade,
            'price_display'         => $price,
            'price_usd'             => $p->price_usd,
            'location'              => $p->location,
            'status'                => $p->status,
            'thumb'                 => $photos[0] ?? null,
            'side'                  => $p->side ?? 'N/A',
            'engine_code_oem'       => $p->engine_code_oem ?? null,
            'transmission_code_oem' => $p->transmission_code_oem ?? null,
            'pin_count'             => $p->pin_count ?? null,
            'gear_alias'            => $p->gear_alias ?? null,
        ];
    }

    // =========================================================
    // GET /parts/compatibility — Year/Make/Model + optional part search
    // Customer enters vehicle (or VIN), optionally narrows by part,
    // sees direct fits + true interchange parts + part-specific coverage
    // =========================================================
    public function compatibility(Request $request)
    {
        $makes = VehicleDatabase::makes();
        $years = VehicleDatabase::years();
        $categories = [
            'Engine','Transmission','Body','Suspension','Electrical',
            'Interior','Cooling','Brakes','Airbag','Fuel','Exhaust','Seat','Wheels',
        ];

        $make     = trim($request->get('make', ''));
        $model    = trim($request->get('model', ''));
        $year     = (int) $request->get('year', 0);
        $partName = trim($request->get('part_name', ''));
        $category = trim($request->get('category', ''));

        $result = null;

        if ($make && $model && $year) {
            $result = $this->buildCompatibilityResult($make, $model, $year, $partName, $category);
        }

        return view('parts.compatibility', compact(
            'makes', 'years', 'categories', 'make', 'model', 'year',
            'partName', 'category', 'result'
        ));
    }

    /**
     * Core compatibility matching logic.
     * When $partName / $category are given, results narrow to that specific part,
     * and a part-specific coverage block is built showing exactly which
     * years/models that part interchanges across (shown even with zero stock).
     */
    private function buildCompatibilityResult(
        string $make,
        string $model,
        int $year,
        string $partName = '',
        string $category = ''
    ): array {
        $rates = $this->getCurrencyRates();
        $oem   = OemDatabase::lookup($make, $model, $year);

        // ── 1. Direct fit parts ──────────────────────────────────
        $directQuery = DB::table('parts_inventory')
            ->where('status', 'Available')
            ->where('brand', 'like', '%' . $make . '%')
            ->where('model', 'like', '%' . $model . '%')
            ->where(function ($q) use ($year) {
                $q->where(function ($sq) use ($year) {
                    $sq->whereNotNull('compat_year_from')
                       ->whereNotNull('compat_year_to')
                       ->where('compat_year_from', '<=', $year)
                       ->where('compat_year_to', '>=', $year);
                })->orWhere(function ($sq) use ($year) {
                    $sq->where(function ($sq2) {
                        $sq2->whereNull('compat_year_from')
                            ->orWhereNull('compat_year_to');
                    })
                    ->where('year_from', '<=', $year)
                    ->where('year_to', '>=', $year);
                });
            });

        if ($partName) $directQuery->where('part_name', 'like', '%' . $partName . '%');
        if ($category) $directQuery->where('part_category', $category);

        $directParts = $directQuery->get();

        // ── 2. OEM interchange parts (different brand/model, same engine/transmission) ──
        $interchangeParts = collect();
        if ($oem['engine_code'] || $oem['transmission_code']) {
            $interchangeQuery = DB::table('parts_inventory')
                ->where('status', 'Available')
                ->where(function ($q) use ($oem) {
                    if ($oem['engine_code']) {
                        $q->orWhere('engine_code_oem', $oem['engine_code']);
                    }
                    if ($oem['transmission_code']) {
                        $q->orWhere('transmission_code_oem', $oem['transmission_code']);
                    }
                })
                ->where(function ($q) use ($make, $model) {
                    $q->where('brand', 'not like', '%' . $make . '%')
                      ->orWhere('model', 'not like', '%' . $model . '%');
                });

            if ($partName) $interchangeQuery->where('part_name', 'like', '%' . $partName . '%');
            if ($category) $interchangeQuery->where('part_category', $category);

            $interchangeParts = $interchangeQuery->get();
        }

        // ── 3. Reference: other vehicles known to share this engine/transmission ──
        $interchangeReference = [];
        $allInterchange = OemDatabase::interchange();
        if ($oem['transmission_code'] && isset($allInterchange[$oem['transmission_code']])) {
            $interchangeReference = $allInterchange[$oem['transmission_code']];
        } elseif ($oem['engine_code'] && isset($allInterchange[$oem['engine_code']])) {
            $interchangeReference = $allInterchange[$oem['engine_code']];
        }

        // ── 4. Part-specific coverage — always shown when a part is specified,
        //      regardless of whether we currently hold matching stock.
        //      Pulls every distinct year-range/model combination across the
        //      WHOLE inventory (any status) for that part name, so the customer
        //      sees the true coverage even if everything is sold out.
        $partCoverage = [];
        if ($partName || $category) {
            $coverageQuery = DB::table('parts_inventory')
                ->select('brand', 'model', 'year_from', 'year_to', 'compat_year_from', 'compat_year_to', 'part_name', 'part_category')
                ->where(function ($q) use ($oem, $make, $model) {
                    if ($oem['engine_code']) {
                        $q->orWhere('engine_code_oem', $oem['engine_code']);
                    }
                    if ($oem['transmission_code']) {
                        $q->orWhere('transmission_code_oem', $oem['transmission_code']);
                    }
                    $q->orWhere(function ($sq) use ($make, $model) {
                        $sq->where('brand', 'like', '%' . $make . '%')
                           ->where('model', 'like', '%' . $model . '%');
                    });
                });

            if ($partName) $coverageQuery->where('part_name', 'like', '%' . $partName . '%');
            if ($category) $coverageQuery->where('part_category', $category);

            $partCoverage = $coverageQuery->distinct()->get()
                ->map(function ($row) {
                    $yearFrom = $row->compat_year_from ?? $row->year_from;
                    $yearTo   = $row->compat_year_to ?? $row->year_to;
                    return [
                        'brand'         => $row->brand,
                        'model'         => $row->model,
                        'year_range'    => $yearFrom === $yearTo ? (string) $yearFrom : "{$yearFrom}–{$yearTo}",
                        'part_name'     => $row->part_name,
                        'part_category' => $row->part_category,
                    ];
                })
                ->unique(fn($r) => $r['brand'] . $r['model'] . $r['year_range'] . $r['part_name'])
                ->values()
                ->all();
        }

        return [
            'vehicle' => [
                'make'  => $make,
                'model' => $model,
                'year'  => $year,
                'oem'   => $oem,
            ],
            'part_name'             => $partName,
            'category'              => $category,
            'direct_parts'          => $directParts->map(fn($p) => $this->formatPart($p, $rates))->values()->all(),
            'interchange_parts'     => $interchangeParts->map(fn($p) => $this->formatPart($p, $rates))->values()->all(),
            'interchange_reference' => $interchangeReference,
            'part_coverage'         => $partCoverage,
            'total_found'           => $directParts->count() + $interchangeParts->count(),
        ];
    }
}
