<?php

namespace App\Integrations\Ai;

use App\Contracts\AIProvider;
use App\Integrations\Support\ChatAnswer;
use App\Services\IntegrationHealthService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LLM-backed provider (any OpenAI-compatible chat-completions endpoint).
 *
 * Safety properties:
 *  - The model is *never* the source of facts. Only the grounded context retrieved by
 *    the application is supplied, and the system prompt forbids invention.
 *  - No email addresses, phone numbers, exact locations or saved itineraries are ever
 *    sent. The context contains public centre/FAQ/service fields only.
 *  - Requests have a hard timeout and bounded retries, and any failure falls back to
 *    the deterministic GroundedAssistantProvider.
 *  - Tool inputs and authorisation are validated server-side; the model cannot trigger
 *    writes, privilege changes, deletions, payments or outbound messages.
 */
class OpenAiProvider implements AIProvider
{
    public function __construct(
        private readonly GroundedAssistantProvider $fallback,
        private readonly IntegrationHealthService $health,
    ) {}

    public function name(): string
    {
        return 'openai';
    }

    public function isAvailable(): bool
    {
        return filled(config('services.ai.openai.key'))
            && config('services.ai.provider') === 'openai';
    }

    public function answer(string $question, array $context = []): ChatAnswer
    {
        if (! $this->isAvailable()) {
            return $this->fallback->answer($question, $context);
        }

        $started = microtime(true);

        try {
            $res = Http::withToken((string) config('services.ai.openai.key'))
                ->timeout((int) config('services.ai.openai.timeout', 20))
                ->retry(2, 300, throw: false)
                ->acceptJson()
                ->post(rtrim((string) config('services.ai.openai.base_url'), '/').'/chat/completions', [
                    'model' => config('services.ai.openai.model'),
                    'max_tokens' => (int) config('services.ai.openai.max_tokens', 600),
                    'temperature' => 0.2,
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt($context)],
                        ['role' => 'user', 'content' => mb_substr($question, 0, 1000)],
                    ],
                ]);

            $this->health->record('openai', 'chat.completions', $res->successful(), $started, $res->status());

            if (! $res->successful()) {
                Log::warning('AI provider returned non-success', ['status' => $res->status()]);

                return $this->fallback->answer($question, $context);
            }

            $content = trim((string) $res->json('choices.0.message.content', ''));

            if ($content === '') {
                return $this->fallback->answer($question, $context);
            }

            $limitation = $this->fallback->asksForLiveData($question) ? 'live_provider_data_required' : null;

            return new ChatAnswer(
                answer: $content,
                sources: [['title' => 'Help & FAQ', 'url' => '/help'], ['title' => 'Centre search', 'url' => '/centres']],
                provider: 'openai',
                grounded: ! empty($context['tools']),
                category: $context['intent'] ?? null,
                toolsUsed: $context['tools'] ?? [],
                limitation: $limitation,
            );
        } catch (\Throwable $e) {
            $this->health->record('openai', 'chat.completions', false, $started, null, 'exception');
            Log::warning('AI provider request failed', ['error' => $e->getMessage()]);

            return $this->fallback->answer($question, $context);
        }
    }

    /** Build the grounding prompt from retrieved, non-personal context. */
    private function systemPrompt(array $context): string
    {
        $centreLines = collect($context['centres'] ?? [])->map(fn ($c) => sprintf(
            '- %s | code %s | %s, %s | verification: %s%s',
            $c['name'], $c['centre_code'], $c['city'], $c['state'], $c['verification_status'],
            $c['is_demo'] ? ' | DEMO DATA (not real)' : ''
        ))->implode("\n");

        $serviceLines = collect($context['services'] ?? [])->map(fn ($s) => sprintf(
            '- %s | %s | %s%s',
            $s['name'], $s['category'], $s['city'] ?? '',
            $s['is_demo'] ? ' | DEMO LISTING (not real)' : ''
        ))->implode("\n");

        $faqLines = collect($context['faqs'] ?? [])->map(fn ($f) => "- Q: {$f['question']}\n  A: {$f['answer']}")->implode("\n");

        $examLines = collect($context['examinations'] ?? [])->map(fn ($e) => "- {$e['name']} | {$e['conducting_authority']} | {$e['official_url']}")->implode("\n");

        return <<<PROMPT
You are the Exam Travel Assist assistant for students travelling in India to examination centres.

ABSOLUTE RULES
1. Use ONLY the CONTEXT below for factual claims. If the context does not contain the answer, say clearly that the information is unavailable and point to the official source (admit card, exam authority website, IRCTC, State RTC, airline).
2. NEVER invent exam centres, centre assignments, examination dates, train/bus timings, seat availability, fares, hotel prices, vacancies, or official policies.
3. Never state that a centre is allotted to a student. Only the official admit card confirms that.
4. Prices from this platform are distance-based estimates unless the context says otherwise — always label them as estimates.
5. This platform does not sell tickets and does not confirm bookings.
6. Answer in the language of the question (English or Hindi). Be concise (max 180 words). Use plain text, short bullet lines starting with "-".

CONTEXT — exam centres in our database:
{$centreLines}

CONTEXT — local service listings:
{$serviceLines}

CONTEXT — approved FAQs:
{$faqLines}

CONTEXT — examinations we hold data for:
{$examLines}
PROMPT;
    }
}
