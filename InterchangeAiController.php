<?php
// FILE: app/Http/Controllers/Admin/InterchangeAiController.php
//
// AI-assisted interchange suggestions for staff during harvest/manual
// inventory entry. Given a part's known engine/transmission codes and
// vehicle data, asks OpenAI (GPT-4o) which other makes/models/years
// likely share the same physical part due to a shared platform/engine/
// transmission — staff review and confirm each suggestion before it's
// added to an interchange group. The AI never writes to the database
// directly; it only proposes, and reuses the existing
// InterchangeService methods (createGroup/addVehicleToGroup) once a
// staff member accepts a suggestion — same trust boundary as the
// manual flow.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\InterchangeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model'    => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert automotive parts interchange specialist. You only respond with valid JSON arrays, no other text or markdown formatting.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
                'max_tokens'  => 1024,
            ]);

            if (!$response->successful()) {
                \Illuminate\Support\Facades\Log::warning('AI interchange suggest failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return response()->json(['error' => 'AI request failed. Please try the manual interchange flow instead.'], 502);
            }

            $text  = $response->json('choices.0.message.content', '');
            $clean = preg_replace('/```json|```/', '', $text);
            $suggestions = json_decode(trim($clean), true);

            if (!is_array($suggestions)) {
                return response()->json(['error' => 'AI response could not be read. Please try again or use the manual interchange flow.'], 502);
            }

            return response()->json(['suggestions' => $suggestions]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AI interchange suggest exception', ['message' => $e->getMessage()]);
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
        ])));

        return <<<PROMPT
You are helping an auto parts recycler identify which other vehicle makes/models/years likely share the exact same physical part, based on shared engine codes, transmission codes, or known platform-sharing between manufacturers (e.g. badge-engineered twins, shared platforms across brands).

Known vehicle/part info:
{$known}

Respond ONLY with a JSON array (no other text, no markdown fences) of suggested interchange matches, each with this exact shape:
[{"brand": "...", "model": "...", "year_from": 2015, "year_to": 2019, "confidence": "high|medium|low", "reason": "short explanation of why this likely shares the part"}]

Only include genuinely plausible matches based on real shared engineering (same engine family, same transmission, confirmed platform-sharing, or badge-engineered variants). If you are not confident about any matches, return an empty array []. Limit to 5 suggestions maximum, ordered by confidence.
PROMPT;
    }
}
