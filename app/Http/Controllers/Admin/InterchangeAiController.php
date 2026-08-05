<?php
// FILE: app/Http/Controllers/Admin/InterchangeAiController.php
//
// AI-assisted interchange suggestions — two entry points:
//
//   1. suggest()          — from a specific PART's edit page. Given a
//                            known part's engine/trans code, asks which
//                            OTHER vehicles likely share that exact part.
//
//   2. suggestForVehicle() — from the Compatibility Checker page. Given
//                            just a vehicle (make/model/year, no part_id
//                            needed), asks AI which OTHER vehicles share
//                            its platform/engine/transmission, THEN
//                            cross-references those against your actual
//                            in-stock inventory to surface parts you
//                            already have that might newly apply.
//
// In both cases the AI never writes to the database directly — it only
// proposes, and staff review + confirm before anything is saved.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\InterchangeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InterchangeAiController extends Controller
{
    // =========================================================
    // POST /admin/interchange/ai-suggest
    // Per-part suggestions (existing — part edit page)
    // =========================================================
    public function suggest(Request $request, InterchangeService $interchange)
    {
        $part = DB::table('parts_inventory')->where('id', $request->part_id)->first();
        abort_if(!$part, 404);

        if (empty($part->engine_code_oem) && empty($part->part_category)) {
            return response()->json(['error' => 'This part has no engine code or category recorded — AI suggestions need at least one to work from.'], 422);
        }

        $prompt = $this->buildPartPrompt($part);
        $result = $this->callOpenAi($prompt);

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 502);
        }

        // NEW: persist every suggestion (AI Knowledge Layer) — this
        // used to be pure display data, discarded the moment the
        // response left this method. Now each suggestion gets a real
        // ai_suggestions row and its own ID, so the reasoning survives
        // regardless of whether staff ever act on it.
        $suggestions = collect($result['data'])->map(function ($s) use ($part) {
            $id = DB::table('ai_suggestions')->insertGetId([
                'part_id'             => $part->id,
                'suggested_make'      => $s['brand'] ?? '',
                'suggested_model'     => $s['model'] ?? '',
                'suggested_year_from' => $s['year_from'] ?? 0,
                'suggested_year_to'   => $s['year_to'] ?? ($s['year_from'] ?? 0),
                'engine_code'         => $part->engine_code_oem ?? null,
                'transmission_code'   => $part->transmission_code_oem ?? null,
                'confidence'          => $s['confidence'] ?? 'low',
                'reason'              => $s['reason'] ?? '',
                'evidence_source'     => 'openai_gpt4o',
                'review_status'       => 'pending',
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
            $s['ai_suggestion_id'] = $id;
            return $s;
        });

        return response()->json(['suggestions' => $suggestions]);
    }

    // =========================================================
    // POST /admin/compatibility/ai-suggest
    // Vehicle-based suggestions (NEW — Compatibility Checker page).
    // No specific part required — just make/model/year. Returns
    // related vehicles WITH engine/trans codes, then cross-references
    // those codes against parts_inventory to surface matching in-stock
    // parts that aren't yet tagged for this vehicle.
    // =========================================================
    public function suggestForVehicle(Request $request)
    {
        $request->validate([
            'make'  => 'required|string',
            'model' => 'required|string',
            'year'  => 'required|integer',
            'part_name' => 'nullable|string',
        ]);

        $make     = strtoupper($request->make);
        $model    = strtoupper($request->model);
        $year     = (int) $request->year;
        $partName = trim($request->part_name ?? '');

        $prompt = $this->buildVehiclePrompt($make, $model, $year, $partName);
        $result = $this->callOpenAi($prompt);

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 502);
        }

        $suggestions = $result['data'];

        // NEW: persist per-vehicle suggestions too, same AI Knowledge
        // Layer as the per-part pathway above. part_id stays null here
        // — this pathway has no single originating part, just a
        // searched vehicle (and optionally a part_name string, which
        // isn't a real parts_inventory row).
        $suggestions = collect($suggestions)->map(function ($s) use ($make, $model) {
            $id = DB::table('ai_suggestions')->insertGetId([
                'part_id'             => null,
                'suggested_make'      => $s['brand'] ?? $make,
                'suggested_model'     => $s['model'] ?? $model,
                'suggested_year_from' => $s['year_from'] ?? 0,
                'suggested_year_to'   => $s['year_to'] ?? ($s['year_from'] ?? 0),
                'engine_code'         => $s['engine_code'] ?? null,
                'transmission_code'   => $s['transmission_code'] ?? null,
                'confidence'          => $s['confidence'] ?? 'low',
                'reason'              => $s['reason'] ?? '',
                'evidence_source'     => 'openai_gpt4o',
                'review_status'       => 'pending',
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
            $s['ai_suggestion_id'] = $id;
            return $s;
        })->values();

        // Cross-reference AI-suggested related vehicles against actual
        // in-stock parts — matched TWO ways so we catch every part
        // category, not just engine/transmission-coded ones:
        //
        //   (a) By vehicle (brand + model + year overlap) — this is
        //       what surfaces headlights, doors, hoods, brake pads,
        //       steering racks, or ANY part tagged to a related vehicle,
        //       regardless of whether it has an OEM engine code at all.
        //
        //   (b) By engine/transmission code — extra net for mechanical
        //       parts that share a drivetrain across vehicles the AI
        //       didn't explicitly list as a full vehicle match.
        $matchingStock = collect();

        $vehicleConditions = collect($suggestions)->filter(fn($s) => !empty($s['brand']) && !empty($s['model']));
        $engineCodes = collect($suggestions)->pluck('engine_code')->filter()->unique();
        $transCodes  = collect($suggestions)->pluck('transmission_code')->filter()->unique();

        if ($vehicleConditions->isNotEmpty() || $engineCodes->isNotEmpty() || $transCodes->isNotEmpty()) {
            $stockQuery = DB::table('parts_inventory')
                ->where(function ($q) use ($vehicleConditions, $engineCodes, $transCodes) {
                    // (a) Vehicle-based match — catches ALL part types for
                    //     the related vehicle (further narrowed below by
                    //     part name/category so a transmission search
                    //     doesn't surface unrelated steering parts etc)
                    foreach ($vehicleConditions as $v) {
                        $yFrom = $v['year_from'] ?? 1900;
                        $yTo   = $v['year_to']   ?? 2100;
                        $q->orWhere(function ($sub) use ($v, $yFrom, $yTo) {
                            $sub->whereRaw('UPPER(brand) = ?', [strtoupper($v['brand'])])
                                ->whereRaw('UPPER(model) = ?', [strtoupper($v['model'])])
                                ->where('year_from', '<=', $yTo)
                                ->where('year_to', '>=', $yFrom);
                        });
                    }
                    // (b) OEM code match — extra net for mechanical parts
                    if ($engineCodes->isNotEmpty()) $q->orWhereIn('engine_code_oem', $engineCodes);
                    if ($transCodes->isNotEmpty())  $q->orWhereIn('transmission_code_oem', $transCodes);
                })
                ->whereIn('status', ['Available', 'Reserved', 'Hold']);

            // Narrow to the SAME part the staff searched for — otherwise
            // a "Steering Rack" search would surface transmissions, doors,
            // or any other part that happens to fit a related vehicle.
            // Match loosely on part_name (contains) so "Steering Rack /
            // Gear Box" still matches "Steering Rack" etc.
            if ($partName !== '') {
                $nameWords = array_filter(explode(' ', strtolower($partName)));
                $firstWord = $nameWords[0] ?? '';
                if ($firstWord) {
                    $stockQuery->where('part_name', 'like', '%' . $firstWord . '%');
                }
            }

            $matchingStock = $stockQuery
                ->select('id', 'part_code', 'part_name', 'part_category', 'brand', 'model', 'year_from', 'year_to',
                         'engine_code_oem', 'transmission_code_oem', 'price_local', 'currency_code',
                         'condition_grade', 'location')
                ->limit(50)
                ->get();
        }

        return response()->json([
            'vehicles'       => $suggestions,
            'matching_stock' => $matchingStock,
        ]);
    }

    // =========================================================
    // Shared OpenAI call
    // =========================================================
    private function callOpenAi(string $prompt): array
    {
        Log::info('AI suggest: starting request', ['key_length' => strlen(env('OPENAI_API_KEY') ?? '')]);

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
                'max_tokens'  => 1200,
            ]);

            Log::info('AI suggest: got response', [
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 500),
            ]);

            if (!$response->successful()) {
                Log::warning('AI suggest failed', ['status' => $response->status(), 'body' => $response->body()]);
                return ['error' => 'AI request failed. Please try the manual interchange flow instead.'];
            }

            $text  = $response->json('choices.0.message.content', '');
            $clean = preg_replace('/```json|```/', '', $text);
            $data  = json_decode(trim($clean), true);

            if (!is_array($data)) {
                Log::warning('AI suggest: could not parse response', ['raw_text' => $text]);
                return ['error' => 'AI response could not be read. Please try again or use the manual interchange flow.'];
            }

            return ['data' => $data];

        } catch (\Exception $e) {
            Log::error('AI suggest exception', ['message' => $e->getMessage()]);
            return ['error' => 'AI request failed: ' . $e->getMessage()];
        }
    }

    // =========================================================
    // Prompt builders
    // =========================================================
    private function buildPartPrompt($part): string
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
- Body panels, lights, bumpers, mirrors: shared body platform, facelift/generation overlap, badge-engineered twins
- Electrical/Electronics (ECU, sensors, switches, wiring harness, infotainment): shared electrical architecture or connector standard across trims/years/models
- Interior parts (seats, dash, console, airbags): shared interior platform across trim levels or model years
- Suspension/brakes/wheels (steering racks, control arms, calipers): shared chassis platform, bolt pattern, or badge-engineered/platform-sharing partners
- Glass, wheels, trim pieces: shared part number across multiple body styles

For parts like starters, alternators, steering racks, and other electrical/mechanical components: even if you cannot confirm an EXACT match, provide medium or low confidence suggestions based on the vehicle's platform, engine family, or known shared-parts relationships within the same manufacturer's lineup during that era.

Respond ONLY with a JSON array (no other text, no markdown fences) of suggested interchange matches, each with this exact shape:
[{"brand": "...", "model": "...", "year_from": 2015, "year_to": 2019, "confidence": "high|medium|low", "reason": "short explanation"}]

Only return an empty array [] if you have absolutely no plausible reasoning to offer. Limit to 6 suggestions maximum, ordered by confidence (high first).
PROMPT;
    }

    private function buildVehiclePrompt(string $make, string $model, int $year, string $partName = ''): string
    {
        $partContext = $partName !== ''
            ? "The staff member is specifically looking for interchange on this part: \"{$partName}\". Focus your reasoning on what actually determines fitment for THIS part type — see guidance below."
            : "No specific part was named — give general platform/engine/transmission-sharing vehicles.";

        return <<<PROMPT
You are an expert automotive parts interchange specialist. Given a vehicle, identify OTHER vehicle makes/models/years that share the same PART as the one specified — NOT necessarily the same engine or transmission unless that IS the part in question.

Vehicle: {$year} {$make} {$model}
{$partContext}

CRITICAL — match your reasoning to what actually determines fitment for this part category:
- Body panels, doors, hoods, fenders, bumpers, mirrors, glass: driven by BODY GENERATION/PLATFORM and facelift boundaries — a body panel fits ALL engine/transmission variants within the same generation. Do NOT gate your suggestions on engine code for these parts; a 4-cylinder and V6 version of the same generation share identical doors.
- Engine/transmission/drivetrain components: driven by engine family/transmission code — this is where engine_code/transmission_code genuinely matter.
- Electrical/electronics (ECU, sensors, switches, wiring, infotainment): driven by trim level and electrical architecture generation, which sometimes crosses engine options within a platform.
- Interior (seats, dash, console, airbags): driven by interior generation/trim, largely independent of engine.
- Suspension/brakes/wheels/steering: driven by chassis platform and sometimes drivetrain (FWD vs AWD) — partially engine/trans-linked, partially not.

For each suggested related vehicle, include engine_code/transmission_code ONLY if genuinely relevant to fitment for this specific part — leave them null for body panels, interior, and most electrical parts where they are not the deciding factor. Do not include them just because you know them if they are irrelevant to whether this part fits.

Respond ONLY with a JSON array (no other text, no markdown fences), each item with this exact shape:
[{"brand": "...", "model": "...", "year_from": 2015, "year_to": 2019, "engine_code": "2AZ-FE or null", "transmission_code": "U241E or null", "confidence": "high|medium|low", "reason": "short explanation specific to why THIS part fits, referencing body generation/platform/engine as appropriate for the part type"}]

Include the vehicle's own generation-mates plus genuinely related vehicles from the same manufacturer or badge-engineered partners. Limit to 8 suggestions maximum, ordered by confidence (high first). If genuinely nothing is known, return an empty array [].
PROMPT;
    }
}
