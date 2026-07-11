<?php
// FILE: app/Http/Controllers/Admin/InterchangeAiController.php
//
// AI-assisted interchange suggestions for staff during harvest/manual
// inventory entry. Given a part's known engine/transmission codes and
// vehicle data, asks OpenAI (GPT-4o) which other makes/models/years
// likely share the same physical part due to shared platform/engine/
// transmission/electrical architecture — staff review and confirm
// each suggestion before it's added to an interchange group. The AI
// never writes to the database directly; it only proposes, and reuses
// the existing InterchangeService methods (createGroup/addVehicleToGroup)
// once a staff member accepts a suggestion — same trust boundary as
// the manual flow.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\InterchangeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InterchangeAiController extends Controller
{
    public function suggest(Request $request, InterchangeService $interchange)
    {
        $part = DB::table('parts_inventory')->where('id', $request->part_id)->first();
        abort_if(!$part, 404);

        if (empty($part->engine_code_oem) && empty($part->part_category)) {
            return response()->json(['error' => 'This part has no engine code or category recorded — AI suggestions need at least one to work from.'], 422);
        }

        $prompt = $this->buildPrompt($part);

        Log::info('AI suggest: starting request', [
            'part_id'    => $part->id,
            'key_length' => strlen(env('OPENAI_API_KEY') ?? ''),
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type'  => 'application/json',
            ])
            ->timeout(25)
            ->connectTimeout(5)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'    => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert automotive parts interchange specialist. You only respond with valid JSON arrays, no other text or markdown formatting.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
                'max_tokens'  => 1024,
            ]);

            Log::info('AI suggest: got response', [
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 500),
            ]);

            if (!$response->successful()) {
                Log::warning('AI interchange suggest failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return response()->json(['error' => 'AI request failed. Please try the manual interchange flow instead.'], 502);
            }

            $text  = $response->json('choices.0.message.content', '');
            $clean = preg_replace('/```json|```/', '', $text);
            $suggestions = json_decode(trim($clean), true);

            if (!is_array($suggestions)) {
                Log::warning('AI suggest: could not parse response', ['raw_text' => $text]);
                return response()->json(['error' => 'AI response could not be read. Please try again or use the manual interchange flow.'], 502);
            }

            return response()->json(['suggestions' => $suggestions]);

        } catch (\Exception $e) {
            Log::error('AI interchange suggest exception', [
                'message' => $e->getMessage(),
                'trace'   => substr($e->getTraceAsString(), 0, 1000),
            ]);
            return response()->json(['error' => 'AI request failed: ' . $e->getMessage()], 502);
        }
    }

    private function buildPrompt($part): string
    {
        $known = trim(implode(' ', array_filter([
            "Brand: {$part->brand}", "Model: {$part->model}",
            "Years: {$part->year_from}-{$part->year_to}",
            $part->engine_code_oem ? "Engine code: {$part->engine_code_oem}" : null,
            $part->transmission_code_oem ?? null ? "Transmission code: {$part->transmission_code_oem}" : null,
            "Part: {$part->part_name}", "Category: {$part->part_category}",
            $part->oem_part_number ? "OEM Part Number: {$part->oem_part_number}" : null,
        ])));

        return <<<PROMPT
You are an expert automotive parts interchange specialist helping an auto parts recycler identify which other vehicle makes/models/years likely accept the exact same physical part as a direct replacement.

Known vehicle/part info:
{$known}

Consider ALL relevant compatibility factors for this specific part category, not just engine/transmission sharing:

- Engine/Transmission parts: shared engine family, transmission code, or drivetrain platform
- Body panels, lights, bumpers, mirrors: shared body platform, facelift/generation overlap, badge-engineered twins (e.g. Toyota/Scion, same-factory rebadges)
- Electrical/Electronics (ECU, sensors, switches, wiring harness, infotainment): shared electrical architecture or connector standard across trims/years/models, even across different engine options
- Interior parts (seats, dash, console, airbags): shared interior platform across trim levels or model years
- Suspension/brakes/wheels (steering racks, control arms, calipers): shared chassis platform, bolt pattern, or badge-engineered/platform-sharing partners (e.g. Hyundai/Kia shared platforms)
- Glass, wheels, trim pieces: shared part number across multiple body styles

For parts like starters, alternators, steering racks, and other electrical/mechanical components: even if you cannot confirm an EXACT match, provide medium or low confidence suggestions based on the vehicle's platform, engine family, or known shared-parts relationships within the same manufacturer's lineup during that era. Staff will physically verify fitment before confirming any suggestion, so it is more useful to suggest plausible candidates with honest confidence levels than to return nothing.

Respond ONLY with a JSON array (no other text, no markdown fences) of suggested interchange matches, each with this exact shape:
[{"brand": "...", "model": "...", "year_from": 2015, "year_to": 2019, "confidence": "high|medium|low", "reason": "short explanation of why this likely shares the part"}]

Only return an empty array [] if you have absolutely no plausible platform, engine, or model-family reasoning to offer. Limit to 6 suggestions maximum, ordered by confidence (high first).
PROMPT;
    }
}
