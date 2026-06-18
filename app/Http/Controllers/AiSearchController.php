<?php

namespace App\Http\Controllers;

use App\Services\AiPartsSearchService;
use App\Services\AiChatbotService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AiSearchController extends Controller
{
    public function __construct(
        private AiPartsSearchService $searchService,
        private AiChatbotService     $chatService,
    ) {}

    // =========================================================================
    // POST /ai/search — Natural language parts search
    // =========================================================================

    /**
     * Accept a plain-text query, return ranked inventory results.
     *
     * Request body:
     *   { "q": "left tail light 2019 Camry", "location": "Waxahachie TX", "currency": "USD" }
     *
     * Response:
     *   { intent, results, also_fits, special_order_prompt, ... }
     */
    public function search(Request $request): JsonResponse
    {
        // ── Validation ────────────────────────────────────────────────────────
        $validated = $request->validate([
            'q'        => 'required|string|min:3|max:200',
            'location' => 'nullable|string|max:60',
            'currency' => 'nullable|string|in:USD,NGN,GHS',
        ]);

        $query   = trim($validated['q']);
        $context = [
            'location' => $validated['location'] ?? null,
            'currency' => $validated['currency'] ?? 'USD',
        ];

        // ── Rate limiting — 30 searches per minute per IP ─────────────────────
        $key = 'ai_search_' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return response()->json([
                'error'   => 'Too many searches. Please wait a moment.',
                'results' => [],
            ], 429);
        }
        RateLimiter::hit($key, 60);

        // ── Run search ────────────────────────────────────────────────────────
        try {
            $result = $this->searchService->search($query, $context);
        } catch (\Exception $e) {
            Log::error('AiSearchController: search failed', [
                'query'   => $query,
                'message' => $e->getMessage(),
            ]);
            return response()->json([
                'error'   => 'Search failed. Please try using the dropdown filters.',
                'results' => [],
            ], 500);
        }

        // ── Add currency conversion if needed ─────────────────────────────────
        $rates = $this->getRates();
        if (!empty($result['results'])) {
            $currency = $context['currency'];
            $result['results'] = array_map(function ($part) use ($rates, $currency) {
                $part['price_display'] = $this->formatPrice($part['price_usd'], $currency, $rates);
                $part['price_ngn']     = round($part['price_usd'] * $rates['NGN']);
                $part['price_ghs']     = round($part['price_usd'] * $rates['GHS'], 2);
                return $part;
            }, $result['results']);
        }

        return response()->json($result);
    }

    // =========================================================================
    // POST /ai/chat — Customer chatbot
    // =========================================================================

    /**
     * Handle a chat message.
     *
     * Request body:
     *   {
     *     "message": "Do you have a front bumper for my Accord?",
     *     "history": [{"role":"user","content":"..."},{"role":"assistant","content":"..."}],
     *     "page_context": {"part_name":"...", "vehicle":"...", "price":"..."}
     *   }
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message'              => 'required|string|min:1|max:500',
            'history'              => 'nullable|array|max:20',
            'history.*.role'       => 'required|in:user,assistant',
            'history.*.content'    => 'required|string|max:1000',
            'page_context'         => 'nullable|array',
        ]);

        // Rate limit: 20 chat messages per minute per IP
        $key = 'ai_chat_' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 20)) {
            return response()->json([
                'reply'           => 'Too many messages. Please wait a moment, then try again.',
                'whatsapp_prompt' => true,
            ], 429);
        }
        RateLimiter::hit($key, 60);

        try {
            $result = $this->chatService->chat(
                $validated['message'],
                $validated['history']      ?? [],
                $validated['page_context'] ?? [],
            );
        } catch (\Exception $e) {
            Log::error('AiChatController: chat failed', ['message' => $e->getMessage()]);
            return response()->json([
                'reply'           => "I can't connect right now. Please WhatsApp us directly.",
                'whatsapp_prompt' => true,
            ], 500);
        }

        return response()->json($result);
    }

    // =========================================================================
    // GET /ai/suggest — Auto-complete suggestions (lightweight, no Claude)
    // =========================================================================

    /**
     * Returns quick part name suggestions from MySQL as user types.
     * No Claude call — pure DB autocomplete for speed.
     */
    public function suggest(Request $request): JsonResponse
    {
        $term = trim($request->get('q', ''));
        if (strlen($term) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $cacheKey = 'suggest_' . md5(strtolower($term));
        $suggestions = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($term) {
            return \DB::table('parts_inventory')
                ->where('status', 'Available')
                ->where('part_name', 'like', '%' . $term . '%')
                ->select('part_name', 'brand', 'model')
                ->distinct()
                ->orderByRaw('LENGTH(part_name)')
                ->limit(8)
                ->get()
                ->map(fn($r) => [
                    'label' => "{$r->part_name} — {$r->brand} {$r->model}",
                    'value' => $r->part_name,
                ])
                ->toArray();
        });

        return response()->json(['suggestions' => $suggestions]);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function getRates(): array
    {
        return Cache::remember('exchange_rates', now()->addHours(24), function () {
            try {
                $res = \Illuminate\Support\Facades\Http::timeout(5)
                    ->get('https://open.er-api.com/v6/latest/USD');
                if ($res->successful()) {
                    $r = $res->json('rates', []);
                    return ['NGN' => round($r['NGN'] ?? 1600, 2), 'GHS' => round($r['GHS'] ?? 15.5, 2)];
                }
            } catch (\Exception $e) {}
            return ['NGN' => 1600, 'GHS' => 15.5];
        });
    }

    private function formatPrice(float $usd, string $currency, array $rates): string
    {
        return match($currency) {
            'NGN'   => '₦' . number_format($usd * $rates['NGN']),
            'GHS'   => 'GH₵' . number_format($usd * $rates['GHS'], 2),
            default => '$' . number_format($usd, 2),
        };
    }
}
