<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AiPartsSearchService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * ============================================================================
 * AiPartsSearchServiceTest
 * Run: php artisan test --filter=AiPartsSearchServiceTest
 * ============================================================================
 */
class AiPartsSearchServiceTest extends TestCase
{
    private AiPartsSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AiPartsSearchService();
        Cache::flush();
    }

    // ── Intent extraction tests ───────────────────────────────────────────────

    /** @test */
    public function it_extracts_intent_from_clear_query()
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [[
                    'type' => 'text',
                    'text' => json_encode([
                        'brand'          => 'Toyota',
                        'model'          => 'Camry',
                        'year'           => 2019,
                        'year_from'      => null,
                        'year_to'        => null,
                        'part_name'      => 'Tail Lamp Assembly',
                        'part_category'  => 'Body',
                        'side'           => 'P/S',
                        'body_style'     => 'Sedan',
                        'confidence'     => 0.97,
                        'intent_summary' => 'Right tail lamp assembly for 2019 Toyota Camry',
                        'search_keywords'=> ['tail lamp', 'Camry', '2019'],
                        'platform_siblings' => [['brand' => 'Lexus', 'model' => 'ES350']],
                    ])
                ]]
            ], 200)
        ]);

        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('select')->andReturnSelf();
        DB::shouldReceive('orWhere')->andReturnSelf();
        DB::shouldReceive('orderByRaw')->andReturnSelf();
        DB::shouldReceive('orderBy')->andReturnSelf();
        DB::shouldReceive('limit')->andReturnSelf();
        DB::shouldReceive('get')->andReturn(collect([]));

        $result = $this->service->search('right tail light 2019 Camry');

        $this->assertEquals('Toyota',            $result['intent']['brand']);
        $this->assertEquals('Camry',             $result['intent']['model']);
        $this->assertEquals(2019,                $result['intent']['year']);
        $this->assertEquals('Tail Lamp Assembly', $result['intent']['part_name']);
        $this->assertEquals('P/S',               $result['intent']['side']);
        $this->assertTrue($result['special_order_prompt']); // no stock in fake DB
    }

    /** @test */
    public function it_normalises_slang_before_sending_to_claude()
    {
        // Pre-normalisation should map "bonnet" → "hood" before Claude sees it
        $captured = null;
        Http::fake([
            'api.anthropic.com/*' => function ($request) use (&$captured) {
                $body = json_decode($request->body(), true);
                $captured = $body['messages'][0]['content'] ?? '';
                return Http::response([
                    'content' => [['type' => 'text', 'text' => '{"part_name":"Hood Assembly","part_category":"Body","brand":null,"model":null,"year":null,"year_from":null,"year_to":null,"side":null,"body_style":null,"condition_preference":null,"oem_number":null,"confidence":0.8,"intent_summary":"Hood assembly","search_keywords":["hood"],"platform_siblings":[]}']]
                ], 200);
            }
        ]);

        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('select')->andReturnSelf();
        DB::shouldReceive('orWhere')->andReturnSelf();
        DB::shouldReceive('orderByRaw')->andReturnSelf();
        DB::shouldReceive('orderBy')->andReturnSelf();
        DB::shouldReceive('limit')->andReturnSelf();
        DB::shouldReceive('get')->andReturn(collect([]));

        $this->service->search('bonnet for Honda');

        // The captured message sent to Claude should have "hood" not "bonnet"
        $this->assertStringContainsString('hood', strtolower($captured));
    }

    /** @test */
    public function it_falls_back_gracefully_when_claude_api_fails()
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([], 500)
        ]);

        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('select')->andReturnSelf();
        DB::shouldReceive('orWhere')->andReturnSelf();
        DB::shouldReceive('orderByRaw')->andReturnSelf();
        DB::shouldReceive('orderBy')->andReturnSelf();
        DB::shouldReceive('limit')->andReturnSelf();
        DB::shouldReceive('get')->andReturn(collect([]));

        $result = $this->service->search('Toyota Camry headlight');

        // Should not throw — should return fallback search result shape
        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('raw_query', $result);
    }

    /** @test */
    public function it_returns_empty_response_for_too_short_query()
    {
        $result = $this->service->search('ab');
        $this->assertEmpty($result['results']);
        $this->assertEquals('Query too short', $result['error']);
    }

    /** @test */
    public function it_caches_intent_extraction_for_same_query()
    {
        $callCount = 0;
        Http::fake([
            'api.anthropic.com/*' => function () use (&$callCount) {
                $callCount++;
                return Http::response([
                    'content' => [['type' => 'text', 'text' => '{"part_name":"Radiator","part_category":"Cooling","brand":"Honda","model":"Accord","year":2020,"year_from":null,"year_to":null,"side":null,"body_style":null,"condition_preference":null,"oem_number":null,"confidence":0.9,"intent_summary":"Radiator for Honda Accord 2020","search_keywords":["radiator","Accord"],"platform_siblings":[]}']]
                ], 200);
            }
        ]);

        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('select')->andReturnSelf();
        DB::shouldReceive('orWhere')->andReturnSelf();
        DB::shouldReceive('orderByRaw')->andReturnSelf();
        DB::shouldReceive('orderBy')->andReturnSelf();
        DB::shouldReceive('limit')->andReturnSelf();
        DB::shouldReceive('get')->andReturn(collect([]));

        // Search same query twice
        $this->service->search('Honda Accord 2020 radiator');
        $this->service->search('Honda Accord 2020 radiator');

        // Claude should only be called ONCE — second call hits cache
        $this->assertEquals(1, $callCount);
    }

    /** @test */
    public function it_maps_supported_brands_correctly()
    {
        // "Volkswagen" should map to "VW" in supported brand list
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => '{"part_name":"Front Strut Assembly","part_category":"Suspension","brand":"VW","model":"Jetta","year":2021,"year_from":null,"year_to":null,"side":null,"body_style":null,"condition_preference":null,"oem_number":null,"confidence":0.88,"intent_summary":"Front strut for 2021 VW Jetta","search_keywords":["strut","Jetta"],"platform_siblings":[{"brand":"Audi","model":"A3"}]}']]
            ], 200)
        ]);

        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('select')->andReturnSelf();
        DB::shouldReceive('orWhere')->andReturnSelf();
        DB::shouldReceive('orderByRaw')->andReturnSelf();
        DB::shouldReceive('orderBy')->andReturnSelf();
        DB::shouldReceive('limit')->andReturnSelf();
        DB::shouldReceive('get')->andReturn(collect([]));

        $result = $this->service->search('Volkswagen Jetta 2021 strut');
        $this->assertEquals('VW', $result['intent']['brand']);
    }

    /** @test */
    public function it_handles_invalid_json_from_claude_gracefully()
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Sorry, I cannot help with that.']]
            ], 200)
        ]);

        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('select')->andReturnSelf();
        DB::shouldReceive('orWhere')->andReturnSelf();
        DB::shouldReceive('orderByRaw')->andReturnSelf();
        DB::shouldReceive('orderBy')->andReturnSelf();
        DB::shouldReceive('limit')->andReturnSelf();
        DB::shouldReceive('get')->andReturn(collect([]));

        // Should fall through to keyword fallback search, not throw
        $result = $this->service->search('2019 Accord bumper');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
    }
}
