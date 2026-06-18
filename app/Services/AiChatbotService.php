<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================================
 * AiChatbotService
 * ============================================================================
 * Handles the customer chat widget on the Auto Zenith website.
 * Answers availability questions, compatibility questions, and
 * general enquiries, then hands off to WhatsApp for complex orders.
 *
 * Cost: ~$0.002 per multi-turn conversation (Haiku pricing)
 * ============================================================================
 */
class AiChatbotService
{
    private string $apiKey;
    private string $model    = 'claude-haiku-4-5';
    private string $endpoint = 'https://api.anthropic.com/v1/messages';
    private int    $maxTokens = 500;

    // Maximum conversation turns to keep in memory (cost control)
    private int $maxHistory = 10;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.key');
    }

    /**
     * Send a chat message and get a response.
     *
     * @param  string $message     Current user message
     * @param  array  $history     Previous [{role, content}] turns
     * @param  array  $pageContext Part or vehicle context from the current page
     * @return array{
     *   reply: string,
     *   action: string|null,
     *   whatsapp_prompt: bool,
     *   search_query: string|null
     * }
     */
    public function chat(string $message, array $history = [], array $pageContext = []): array
    {
        $systemPrompt = $this->buildChatSystemPrompt($pageContext);

        // Trim history to last N turns to control token usage
        $trimmedHistory = array_slice($history, -$this->maxHistory);

        // Append current message
        $messages = array_merge(
            $trimmedHistory,
            [['role' => 'user', 'content' => trim($message)]]
        );

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(12)->post($this->endpoint, [
                'model'      => $this->model,
                'max_tokens' => $this->maxTokens,
                'system'     => $systemPrompt,
                'messages'   => $messages,
            ]);

            if ($response->failed()) {
                Log::error('Chatbot API error', ['status' => $response->status()]);
                return $this->errorReply();
            }

            $reply = trim($response->json('content.0.text', ''));
            return $this->parseReply($reply);

        } catch (\Exception $e) {
            Log::error('Chatbot exception', ['message' => $e->getMessage()]);
            return $this->errorReply();
        }
    }

    private function buildChatSystemPrompt(array $pageContext = []): string
    {
        $contextStr = '';
        if (!empty($pageContext['part_name'])) {
            $contextStr = "The customer is currently viewing: {$pageContext['part_name']} for {$pageContext['vehicle']} at {$pageContext['price']}.";
        }

        return <<<PROMPT
You are the Auto Zenith Parts customer assistant — friendly, knowledgeable, and concise. You help customers find the right used auto parts.

{$contextStr}

About Auto Zenith Parts:
- Used and new spare parts for: Toyota, Lexus, Kia, Hyundai, Nissan, Mercedes-Benz, Infiniti, Ford, GM, Chevrolet, Acura, VW, Honda
- Locations: Waxahachie TX (HQ), Elkhorn WI, Ile-Ife Nigeria, Ibadan Nigeria, Oshodi Lagos, Accra Ghana
- Contacts: USA +1 (512) 587-3425 | Nigeria WhatsApp +234 706 441 3764 | Website: autozenithparts.com
- Services: Used/new spare parts, imports/exports, auction brokerage (Copart, Manheim, ADESA, ACV, LAA)

Your rules:
1. Keep replies SHORT — 2-3 sentences max for simple questions
2. For part availability questions, let them know they can search by VIN or use the search bar, then offer to help narrow down
3. For compatibility questions, use your knowledge of auto platforms (Toyota/Lexus TNGA-K, Honda/Acura, Nissan/Infiniti, Hyundai/Kia, VW MQB, GM T1)
4. For pricing questions, direct to the listing or WhatsApp for a quote
5. For complex orders, special parts, or bulk enquiries, end with: [WHATSAPP_HANDOFF]
6. For part searches, end with: [SEARCH: their search query in plain English]
7. NEVER make up part prices or availability — you don't have real-time stock data
8. If asked about airbags, emphasise the safety-critical matching requirements
9. Be warm but efficient — customers want answers, not essays
PROMPT;
    }

    private function parseReply(string $reply): array
    {
        $action         = null;
        $whatsappPrompt = false;
        $searchQuery    = null;

        // Detect WhatsApp handoff signal
        if (str_contains($reply, '[WHATSAPP_HANDOFF]')) {
            $reply          = str_replace('[WHATSAPP_HANDOFF]', '', $reply);
            $whatsappPrompt = true;
            $action         = 'whatsapp';
        }

        // Detect search query signal
        if (preg_match('/\[SEARCH:\s*(.+?)\]/i', $reply, $matches)) {
            $searchQuery = trim($matches[1]);
            $reply       = preg_replace('/\[SEARCH:\s*.+?\]/i', '', $reply);
            $action      = 'search';
        }

        return [
            'reply'           => trim($reply),
            'action'          => $action,
            'whatsapp_prompt' => $whatsappPrompt,
            'search_query'    => $searchQuery,
        ];
    }

    private function errorReply(): array
    {
        return [
            'reply'           => "I'm having a quick issue connecting. Please try again or reach us on WhatsApp — we respond fast!",
            'action'          => 'whatsapp',
            'whatsapp_prompt' => true,
            'search_query'    => null,
        ];
    }
}
