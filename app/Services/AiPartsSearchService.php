<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================================
 * AiPartsSearchService
 * ============================================================================
 * Sends a natural language parts query to Claude Haiku, extracts structured
 * intent (brand, model, year, part name), queries the MySQL inventory, and
 * returns ranked results with compatibility suggestions.
 *
 * Usage:
 *   $service = new AiPartsSearchService();
 *   $results = $service->search("left tail light 2019 Camry");
 *
 * Cost: ~$0.001 per search query (Claude Haiku pricing as of 2025)
 * ============================================================================
 */
class AiPartsSearchService
{
    private string $apiKey;
    private string $model    = 'claude-haiku-4-5';
    private string $endpoint = 'https://api.anthropic.com/v1/messages';
    private int    $maxTokens = 800;

    // Supported brands
    private array $supportedBrands = [
        'Toyota', 'Lexus', 'Kia', 'Hyundai', 'Nissan', 'Mercedes-Benz',
        'Infiniti', 'Ford', 'GM', 'Chevrolet', 'Acura', 'VW', 'Honda',
    ];

    // Part name normalisation map (customer slang → standard names)
    private array $termNormaliser = [
        'bonnet'         => 'Hood',
        'boot'           => 'Trunk Lid',
        'windscreen'     => 'Windshield',
        'back light'     => 'Tail Lamp Assembly',
        'rear light'     => 'Tail Lamp Assembly',
        'indicator'      => 'Turn Signal',
        'blinker'        => 'Turn Signal',
        'gearbox'        => 'Transmission',
        'gear box'       => 'Transmission',
        'fan belt'       => 'Serpentine Belt',
        'water pump'     => 'Water Pump',
        'sump'           => 'Oil Pan',
        'wing'           => 'Fender',
        'quarter panel'  => 'Quarter Panel',
        'running board'  => 'Running Board',
        'fog light'      => 'Fog Lamp Assembly',
        'fog lamp'       => 'Fog Lamp Assembly',
        'back bumper'    => 'Rear Bumper Cover',
        'front bumper'   => 'Front Bumper Cover',
        'side mirror'    => 'Door Mirror Assembly',
        'wing mirror'    => 'Door Mirror Assembly',
        'strut'          => 'Shock Absorber/Strut Assembly',
        'shock absorber' => 'Shock Absorber/Strut Assembly',
        'cv joint'       => 'CV Axle Assembly',
        'drive shaft'    => 'CV Axle Assembly',
        'half shaft'     => 'CV Axle Assembly',
        'aircon'         => 'AC Compressor',
        'a/c'            => 'AC Compressor',
        'intercooler'    => 'Charge Air Cooler',
        'diff'           => 'Differential Assembly',
    ];

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.key');
    }

    // =========================================================================
    // Main entry point
    // =========================================================================

    /**
     * Search for parts using a natural language query.
     *
     * @param  string $query     Raw customer input, e.g. "left tail light 2019 Camry"
     * @param  array  $context   Optional extra filters (location, currency)
     * @return array{
     *   intent: array,
     *   results: array,
     *   also_fits: array,
     *   special_order_prompt: bool,
     *   raw_query: string,
     *   cached: bool
     * }
     */
    public function search(string $query, array $context = []): array
    {
        $query = trim($query);

        if (strlen($query) < 3) {
            return $this->emptyResponse($query, 'Query too short');
        }

        // Pre-normalise obvious slang before sending to Claude
        $normalisedQuery = $this->preNormalise($query);

        // Cache key — hash of normalised query
        $cacheKey = 'ai_search_' . md5(strtolower($normalisedQuery));

        // Cache intent extraction for 24h (not the inventory results — those change)
        $intent = Cache::remember($cacheKey, now()->addHours(24), function () use ($normalisedQuery) {
            return $this->extractIntent($normalisedQuery);
        });

        if (! $intent || ! empty($intent['error'])) {
            Log::warning('AiPartsSearch: intent extraction failed', ['query' => $query, 'intent' => $intent]);
            return $this->fallbackSearch($query, $context);
        }

        // Run the inventory query with the extracted intent
        $results    = $this->queryInventory($intent, $context);
        $alsoFits   = $this->getCompatibilityExpansion($intent, $results);
        $noResults  = empty($results);

        return [
            'intent'               => $intent,
            'results'              => $results,
            'also_fits'            => $alsoFits,
            'special_order_prompt' => $noResults,
            'raw_query'            => $query,
            'normalised_query'     => $normalisedQuery,
            'cached'               => false,
            'result_count'         => count($results),
        ];
    }

    // =========================================================================
    // Step 1 — Pre-normalise obvious slang terms
    // =========================================================================

    private function preNormalise(string $query): string
    {
        $lower = strtolower($query);
        foreach ($this->termNormaliser as $slang => $standard) {
            $lower = str_replace($slang, strtolower($standard), $lower);
        }
        return $lower;
    }

    // =========================================================================
    // Step 2 — Claude Haiku: extract structured intent from query
    // =========================================================================

    /**
     * Send query to Claude Haiku and get back structured JSON intent.
     * Returns null on API failure.
     */
    private function extractIntent(string $query): ?array
    {
        $systemPrompt = $this->buildSystemPrompt();
        $userMessage  = "Extract parts search intent from this query: \"{$query}\"";

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(10)->post($this->endpoint, [
                'model'      => $this->model,
                'max_tokens' => $this->maxTokens,
                'system'     => $systemPrompt,
                'messages'   => [
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);

            if ($response->failed()) {
                Log::error('Claude API error', [
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                    'query'    => $query,
                ]);
                return null;
            }

            $content = $response->json('content.0.text', '');
            return $this->parseIntentJson($content);

        } catch (\Exception $e) {
            Log::error('Claude API exception', ['message' => $e->getMessage(), 'query' => $query]);
            return null;
        }
    }

    /**
     * Build the system prompt that tells Claude exactly what to extract.
     */
    private function buildSystemPrompt(): string
    {
        $brands = implode(', ', $this->supportedBrands);

        return <<<PROMPT
You are a JSON-only auto parts intent extractor for Auto Zenith Parts, a used auto parts dealer.

Your only job is to parse a customer's natural language query and return a JSON object. Never explain. Never add text outside the JSON.

Supported brands: {$brands}

Extract these fields:
- "brand": string or null — one of the supported brands exactly as listed (e.g. "Toyota", "Mercedes-Benz", "VW"). Map "Volkswagen" → "VW". If brand not supported or unclear, null.
- "model": string or null — vehicle model name (e.g. "Camry", "Civic", "F-150"). Normalise common variants: "rav4" → "RAV4", "f150" → "F-150", "gti" → "Golf GTI".
- "year": integer or null — model year (e.g. 2019). If a range is given (e.g. "2018-2020"), use the middle or most specific. If "latest" or current context, use null.
- "year_from": integer or null — start of year range if given.
- "year_to": integer or null — end of year range if given.
- "part_name": string — standardised part name. Examples: "Tail Lamp Assembly", "Front Bumper Cover", "Transmission", "Engine Assembly", "CV Axle Assembly", "Front Strut Assembly", "Alternator", "Radiator", "Hood Assembly", "Door Front D/S", "Door Front P/S". Use your knowledge of auto parts to infer correct name.
- "part_category": string — one of: Engine, Transmission, Body, Suspension, Electrical, Interior, Cooling, Brakes, Airbag, Fuel, Exhaust, Seat. Infer from part name.
- "side": string or null — "D/S" (driver/left), "P/S" (passenger/right), or null if not specified. Map: left→D/S, right→P/S, driver→D/S, passenger→P/S.
- "body_style": string or null — "Sedan", "Coupe", "SUV", "Truck", "Hatchback", "Wagon" or null.
- "condition_preference": string or null — "A", "B", "C", "New", or null.
- "oem_number": string or null — if customer provides a part number.
- "confidence": float — 0.0 to 1.0. How confident are you in the extracted brand/model/part? 1.0 = very clear query.
- "intent_summary": string — one-line human-readable summary (e.g. "Right tail lamp for 2019 Toyota Camry Sedan").
- "search_keywords": array of strings — 2-4 keywords to use in a fallback text search.
- "platform_siblings": array — brands/models that share platform and may have compatible parts. E.g. for Toyota Camry: [{"brand":"Lexus","model":"ES350"}]. Use your knowledge.

Return ONLY valid JSON. No markdown, no explanation, no code fences.

Example output:
{"brand":"Toyota","model":"Camry","year":2019,"year_from":null,"year_to":null,"part_name":"Tail Lamp Assembly","part_category":"Body","side":"P/S","body_style":"Sedan","condition_preference":null,"oem_number":null,"confidence":0.97,"intent_summary":"Right tail lamp assembly for 2019 Toyota Camry","search_keywords":["tail lamp","Camry","2019"],"platform_siblings":[{"brand":"Lexus","model":"ES350"}]}
PROMPT;
    }

    /**
     * Parse the JSON Claude returns — handles edge cases.
     */
    private function parseIntentJson(string $content): ?array
    {
        $content = trim($content);

        // Strip any accidental markdown fences
        $content = preg_replace('/^```json\s*/i', '', $content);
        $content = preg_replace('/\s*```$/i', '', $content);
        $content = trim($content);

        // Find first { ... } block in case of extra text
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $content = $matches[0];
        }

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Claude returned invalid JSON', ['content' => $content]);
            return null;
        }

        // Validate minimum required fields
        if (empty($decoded['part_name'])) {
            return null;
        }

        // Ensure brand is in supported list
        if (!empty($decoded['brand']) && !in_array($decoded['brand'], $this->supportedBrands)) {
            $decoded['brand'] = null;
        }

        return $decoded;
    }

    // =========================================================================
    // Step 3 — Query MySQL inventory with extracted intent
    // =========================================================================

    private function queryInventory(array $intent, array $context = []): array
    {
        $query = DB::table('parts_inventory')
            ->where('status', 'Available')
            ->select([
                'id', 'part_code', 'brand', 'model', 'year_from', 'year_to',
                'part_name', 'part_category', 'side', 'condition_grade',
                'price_usd', 'location', 'mileage', 'donor_vin', 'colour',
                'oem_part_number', 'photos', 'description', 'stock_qty',
                'body_style', 'engine_code', 'trim_level', 'created_at',
            ]);

        // ── Brand filter ──────────────────────────────────────────────────────
        if (!empty($intent['brand'])) {
            $query->where('brand', $intent['brand']);
        }

        // ── Model filter (fuzzy) ──────────────────────────────────────────────
        if (!empty($intent['model'])) {
            $query->where('model', 'like', '%' . $intent['model'] . '%');
        }

        // ── Year filter — check if year falls within part's compatibility range
        $year = $intent['year'] ?? null;
        if ($year) {
            $query->where('year_from', '<=', $year)
                  ->where('year_to',   '>=', $year);
        } elseif (!empty($intent['year_from']) && !empty($intent['year_to'])) {
            // Range given — part's range must OVERLAP the requested range
            $query->where('year_from', '<=', $intent['year_to'])
                  ->where('year_to',   '>=', $intent['year_from']);
        }

        // ── Part name search — multiple strategies ────────────────────────────
        if (!empty($intent['part_name'])) {
            $partTokens = explode(' ', strtolower($intent['part_name']));
            $query->where(function ($q) use ($intent, $partTokens) {
                // Exact part name match (highest priority)
                $q->orWhere('part_name', 'like', '%' . $intent['part_name'] . '%');

                // Token-based matching (e.g. "Tail" AND "Lamp" must both appear)
                if (count($partTokens) > 1) {
                    $q->orWhere(function ($inner) use ($partTokens) {
                        foreach ($partTokens as $token) {
                            if (strlen($token) > 2) { // skip short words
                                $inner->where('part_name', 'like', '%' . $token . '%');
                            }
                        }
                    });
                }
            });
        }

        // ── Part category filter ──────────────────────────────────────────────
        if (!empty($intent['part_category'])) {
            $query->orWhere('part_category', $intent['part_category']);
        }

        // ── Side filter (D/S vs P/S) ──────────────────────────────────────────
        if (!empty($intent['side'])) {
            $query->where(function ($q) use ($intent) {
                $q->where('side', $intent['side'])
                  ->orWhere('side', 'N/A'); // parts without a side apply to all
            });
        }

        // ── Body style filter ─────────────────────────────────────────────────
        if (!empty($intent['body_style'])) {
            $query->where(function ($q) use ($intent) {
                $q->where('body_style', $intent['body_style'])
                  ->orWhereNull('body_style');
            });
        }

        // ── Condition preference ──────────────────────────────────────────────
        if (!empty($intent['condition_preference'])) {
            $query->where('condition_grade', $intent['condition_preference']);
        }

        // ── OEM part number exact match ───────────────────────────────────────
        if (!empty($intent['oem_number'])) {
            $query->orWhere('oem_part_number', $intent['oem_number']);
        }

        // ── Location filter from context ──────────────────────────────────────
        if (!empty($context['location'])) {
            $query->where('location', $context['location']);
        }

        // ── Keyword fallback search ───────────────────────────────────────────
        if (!empty($intent['search_keywords'])) {
            $query->orWhere(function ($q) use ($intent) {
                foreach ($intent['search_keywords'] as $keyword) {
                    if (strlen($keyword) > 2) {
                        $q->orWhere('part_name',       'like', '%' . $keyword . '%')
                          ->orWhere('description',     'like', '%' . $keyword . '%')
                          ->orWhere('oem_part_number', 'like', '%' . $keyword . '%');
                    }
                }
            });
        }

        // ── Sort: exact side match first, then grade A first, then price ──────
        $query->orderByRaw("
            CASE
                WHEN side = ? THEN 0
                WHEN side = 'N/A' THEN 1
                ELSE 2
            END
        ", [$intent['side'] ?? 'N/A'])
        ->orderByRaw("FIELD(condition_grade, 'A', 'New', 'B', 'C')")
        ->orderBy('price_usd', 'asc')
        ->limit(20);

        $rows = $query->get();

        // Score and rank results
        return $this->scoreAndRank($rows, $intent);
    }

    /**
     * Score each result by how well it matches the intent.
     * Returns array sorted by score descending.
     */
    private function scoreAndRank(\Illuminate\Support\Collection $rows, array $intent): array
    {
        $scored = $rows->map(function ($part) use ($intent) {
            $score = 0;

            // Brand exact match
            if (!empty($intent['brand']) && $part->brand === $intent['brand']) {
                $score += 30;
            }

            // Model match
            if (!empty($intent['model']) && stripos($part->model, $intent['model']) !== false) {
                $score += 25;
            }

            // Year within range
            $year = $intent['year'] ?? null;
            if ($year && $part->year_from <= $year && $part->year_to >= $year) {
                $score += 20;
                // Bonus: narrower range = more specific part
                $rangeWidth = $part->year_to - $part->year_from;
                $score += max(0, 5 - $rangeWidth);
            }

            // Part name similarity
            if (!empty($intent['part_name'])) {
                $partLower   = strtolower($intent['part_name']);
                $stockLower  = strtolower($part->part_name);
                if ($stockLower === $partLower) {
                    $score += 20;
                } elseif (str_contains($stockLower, $partLower) || str_contains($partLower, $stockLower)) {
                    $score += 12;
                } else {
                    // Count matching tokens
                    $intentTokens = explode(' ', $partLower);
                    $stockTokens  = explode(' ', $stockLower);
                    $matches      = count(array_intersect($intentTokens, $stockTokens));
                    $score += $matches * 4;
                }
            }

            // Side match
            if (!empty($intent['side'])) {
                if ($part->side === $intent['side']) {
                    $score += 10;
                } elseif ($part->side === 'N/A') {
                    $score += 3;
                }
            }

            // Condition grade bonus
            $score += match($part->condition_grade) {
                'A'   => 5,
                'New' => 4,
                'B'   => 2,
                default => 0,
            };

            // Low mileage bonus
            if ($part->mileage && $part->mileage < 60000)  $score += 3;
            if ($part->mileage && $part->mileage < 30000)  $score += 2;

            return [
                'part'  => $part,
                'score' => $score,
            ];
        });

        return $scored
            ->sortByDesc('score')
            ->take(12)
            ->map(fn($item) => $this->formatPartResult($item['part'], $item['score']))
            ->values()
            ->toArray();
    }

    /**
     * Format a DB row into the response array.
     */
    private function formatPartResult(object $part, int $score): array
    {
        $photos = json_decode($part->photos ?? '[]', true);

        return [
            'id'             => $part->id,
            'part_code'      => $part->part_code,
            'brand'          => $part->brand,
            'model'          => $part->model,
            'year_from'      => $part->year_from,
            'year_to'        => $part->year_to,
            'part_name'      => $part->part_name,
            'part_category'  => $part->part_category,
            'side'           => $part->side,
            'condition_grade'=> $part->condition_grade,
            'price_usd'      => (float) $part->price_usd,
            'location'       => $part->location,
            'mileage'        => $part->mileage,
            'oem_part_number'=> $part->oem_part_number,
            'colour'         => $part->colour,
            'body_style'     => $part->body_style,
            'stock_qty'      => $part->stock_qty,
            'thumb'          => $photos[0] ?? null,
            'photos_count'   => count($photos),
            'description'    => $part->description,
            'match_score'    => $score,
            'compatibility_label' => "Fits: {$part->brand} {$part->model} {$part->year_from}" .
                ($part->year_to !== $part->year_from ? "–{$part->year_to}" : ''),
        ];
    }

    // =========================================================================
    // Step 4 — Compatibility expansion ("Also Fits")
    // =========================================================================

    /**
     * Look up the parts_compatibility table to find "Also Fits" suggestions.
     * Returns array of compatible vehicle matches that may expand the search.
     */
    private function getCompatibilityExpansion(array $intent, array $currentResults): array
    {
        if (empty($intent['brand']) || empty($intent['part_name'])) {
            return [];
        }

        $year = $intent['year'] ?? null;

        $rules = DB::table('parts_compatibility')
            ->where('brand', $intent['brand'])
            ->where(function ($q) use ($intent) {
                $q->where('part_category', $intent['part_category'] ?? '')
                  ->orWhere('part_category', 'like', '%' . explode(' ', $intent['part_name'])[0] . '%');
            })
            ->when(!empty($intent['model']), fn($q) =>
                $q->where('model', 'like', '%' . $intent['model'] . '%')
            )
            ->when($year, fn($q) =>
                $q->where('year_from', '<=', $year)->where('year_to', '>=', $year)
            )
            ->limit(5)
            ->get();

        $alsoFits = [];
        foreach ($rules as $rule) {
            $siblings = json_decode($rule->also_fits ?? '[]', true);
            foreach ($siblings as $sibling) {
                // Check if we already have results from this sibling brand
                $alreadyShown = collect($currentResults)->contains(fn($r) =>
                    $r['brand'] === ($sibling['brand'] ?? '') &&
                    $r['model'] === ($sibling['model'] ?? '')
                );

                if (!$alreadyShown) {
                    $alsoFits[] = [
                        'brand'      => $sibling['brand'] ?? '',
                        'model'      => $sibling['model'] ?? '',
                        'year_from'  => $sibling['year_from'] ?? null,
                        'year_to'    => $sibling['year_to'] ?? null,
                        'notes'      => $sibling['notes'] ?? '',
                        'note'       => $rule->interchange_note ?? '',
                    ];
                }
            }
        }

        // Also use Claude's platform_siblings from intent
        foreach ($intent['platform_siblings'] ?? [] as $sibling) {
            $exists = collect($alsoFits)->contains(fn($a) =>
                $a['brand'] === $sibling['brand'] && $a['model'] === $sibling['model']
            );
            if (!$exists) {
                $alsoFits[] = [
                    'brand'     => $sibling['brand'],
                    'model'     => $sibling['model'],
                    'year_from' => null,
                    'year_to'   => null,
                    'notes'     => 'Platform-shared — may have compatible parts',
                    'note'      => 'Suggested by AI platform analysis',
                ];
            }
        }

        return array_slice(array_unique($alsoFits, SORT_REGULAR), 0, 5);
    }

    // =========================================================================
    // Fallback: keyword search when Claude fails
    // =========================================================================

    private function fallbackSearch(string $query, array $context = []): array
    {
        $words   = preg_split('/\s+/', trim($query));
        $results = DB::table('parts_inventory')
            ->where('status', 'Available')
            ->where(function ($q) use ($words) {
                foreach ($words as $word) {
                    if (strlen($word) > 2) {
                        $q->orWhere('part_name', 'like', '%' . $word . '%')
                          ->orWhere('model',      'like', '%' . $word . '%')
                          ->orWhere('brand',      'like', '%' . $word . '%');
                    }
                }
            })
            ->select([
                'id', 'part_code', 'brand', 'model', 'year_from', 'year_to',
                'part_name', 'part_category', 'side', 'condition_grade',
                'price_usd', 'location', 'mileage', 'oem_part_number',
                'colour', 'photos', 'stock_qty', 'body_style', 'description',
            ])
            ->limit(12)
            ->get()
            ->map(fn($p) => $this->formatPartResult($p, 0))
            ->values()
            ->toArray();

        return [
            'intent'               => ['intent_summary' => 'Keyword search: ' . $query],
            'results'              => $results,
            'also_fits'            => [],
            'special_order_prompt' => empty($results),
            'raw_query'            => $query,
            'normalised_query'     => $query,
            'cached'               => false,
            'result_count'         => count($results),
            'fallback'             => true,
        ];
    }

    private function emptyResponse(string $query, string $reason): array
    {
        return [
            'intent'               => [],
            'results'              => [],
            'also_fits'            => [],
            'special_order_prompt' => true,
            'raw_query'            => $query,
            'normalised_query'     => $query,
            'cached'               => false,
            'result_count'         => 0,
            'error'                => $reason,
        ];
    }
}
