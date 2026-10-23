<?php

namespace App\Integrations\Ai;

use App\Contracts\AIProvider;
use App\Integrations\Support\ChatAnswer;
use App\Services\AssistantContextBuilder;

/**
 * Deterministic, grounded answerer used as the default AI provider.
 *
 * It performs **no external call**, costs nothing, cannot hallucinate and therefore
 * always works — even offline. Every answer is composed directly from rows retrieved
 * by AssistantContextBuilder, with the source and freshness made explicit.
 *
 * This is the provider the acceptance test relies on: if it has no evidence, it says
 * so instead of inventing centres, fares, timetables or availability.
 */
class GroundedAssistantProvider implements AIProvider
{
    public function name(): string
    {
        return 'faq';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function answer(string $question, array $context = []): ChatAnswer
    {
        $intent = $context['intent'] ?? AssistantContextBuilder::INTENT_UNKNOWN;
        $sources = [];
        $limitation = null;

        // Questions that can never be answered from this platform's data.
        if ($this->asksForLiveData($question)) {
            $limitation = 'live_provider_data_required';
        }

        $lines = [];

        if (! empty($context['faqs'])) {
            $top = $context['faqs'][0];
            $lines[] = $top['answer'];
            $sources[] = ['title' => 'Help & FAQ — '.$top['question'], 'url' => '/help'];
            if (! empty($top['source_url'])) {
                $sources[] = ['title' => 'Official source', 'url' => $top['source_url']];
            }
        }

        if (! empty($context['centres'])) {
            $rows = array_map(function (array $c) {
                $flags = [$c['verification_status']];
                if ($c['is_demo']) {
                    $flags[] = 'DEMO DATA';
                }
                if (! $c['has_coordinates']) {
                    $flags[] = 'no verified coordinates';
                }

                return sprintf(
                    '• %s (code %s) — %s, %s [%s]',
                    $c['name'],
                    $c['centre_code'],
                    $c['city'],
                    $c['state'],
                    implode(', ', $flags),
                );
            }, $context['centres']);

            $lines[] = "Centres matching your question in our database:\n".implode("\n", $rows)
                ."\n\nPresence in this list does **not** mean a centre is allotted to you. Confirm your centre, code and address only from your official admit card.";
            $sources[] = ['title' => 'Centre search', 'url' => '/centres'];
        }

        if (! empty($context['services'])) {
            $rows = array_map(fn (array $s) => sprintf(
                '• %s (%s) — %s%s%s',
                $s['name'],
                str_replace('_', ' ', $s['category']),
                $s['city'] ?? 'city not recorded',
                $s['price_from'] !== null ? ' · listed from ₹'.number_format($s['price_from']) : ' · price not recorded',
                $s['is_demo'] ? ' · DEMO LISTING' : '',
            ), $context['services']);

            $lines[] = "Listings near you (always call to confirm price, vacancy and timings — we never show live availability):\n".implode("\n", $rows);
            $sources[] = ['title' => 'Nearby services', 'url' => '/places'];
        }

        if (! empty($context['examinations'])) {
            $rows = array_map(fn (array $e) => sprintf(
                '• %s — %s · %d centre(s) in our database%s',
                $e['name'],
                $e['conducting_authority'] ?: 'authority not recorded',
                $e['centres_count'],
                $e['official_url'] ? ' · '.$e['official_url'] : '',
            ), $context['examinations']);

            $lines[] = "Examinations we hold centre data for:\n".implode("\n", $rows)
                ."\n\nExamination dates, eligibility and city intimation are published by the conducting authority only.";
            $sources[] = ['title' => 'Examinations', 'url' => '/centres'];
        }

        if ($intent === AssistantContextBuilder::INTENT_JOURNEY) {
            $lines[] = 'For journey planning: open **Plan Journey**, pick your centre, your starting city and your date. '
                .'The planner shows train/bus/car costs as *disclosed distance-based ranges* and marks flight prices as unavailable, '
                .'because no public API publishes live Indian fares or seat availability. Booking and live timings always happen on the official site (IRCTC, your State RTC, the airline).';
            $sources[] = ['title' => 'Plan journey', 'url' => '/journey'];
        }

        if ($limitation !== null) {
            $lines[] = 'I cannot give you live fares, seat availability, train or bus timings, hotel prices or vacancy — '
                .'no public, permitted data source exists for that and I will not guess. Check the official site shown in the search results.';
        }

        if ($lines === []) {
            return new ChatAnswer(
                answer: "I don't have verified information for that yet, and I won't guess about exam centres, fares, timings or availability.\n\n"
                    .'You can: ① search centres, ② open the journey planner for disclosed estimates plus official booking links, '
                    ."③ browse nearby services, or ④ read the Help & FAQ. For anything official, your admit card and the exam authority's website are the only authoritative sources.",
                sources: [['title' => 'Help & FAQ', 'url' => '/help'], ['title' => 'Centre search', 'url' => '/centres']],
                provider: 'fallback',
                grounded: false,
                category: $intent,
                toolsUsed: $context['tools'] ?? [],
                limitation: 'no_matching_evidence',
            );
        }

        return new ChatAnswer(
            answer: implode("\n\n", $lines),
            sources: $this->dedupeSources($sources),
            provider: 'grounded_db',
            grounded: true,
            category: $intent,
            toolsUsed: $context['tools'] ?? [],
            limitation: $limitation,
        );
    }

    /** True when the question can only be answered by a live, licensed data source. */
    public function asksForLiveData(string $question): bool
    {
        $q = mb_strtolower($question);

        foreach ([
            'seat availability', 'seats available', 'available seats', 'vacancy', 'vacancies', 'room available',
            'live fare', 'live price', 'live timing', 'timetable', 'schedule', 'running status', 'pnr',
            'ticket price', 'book a ticket', 'booking available', 'surge', 'is there a train', 'next train',
            'how much is the fare', 'current fare', 'today\'s fare', 'live availability',
        ] as $needle) {
            if (str_contains($q, $needle)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<int,array{title:string,url:string}> $sources */
    private function dedupeSources(array $sources): array
    {
        $seen = [];
        $out = [];

        foreach ($sources as $s) {
            if (! isset($s['url']) || isset($seen[$s['url']])) {
                continue;
            }
            $seen[$s['url']] = true;
            $out[] = $s;
        }

        return array_slice($out, 0, 5);
    }
}
