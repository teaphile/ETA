<?php

namespace App\Services;

use App\Integrations\Support\CityDirectory;
use App\Integrations\Support\DataStatus;
use App\Models\ExamCentre;
use App\Models\Examination;
use App\Models\Faq;
use App\Models\Itinerary;
use App\Models\LocalService;

/**
 * Read-only grounding for the assistant.
 *
 * Every factual answer must be traceable to rows returned by these methods. The
 * builder is deliberately narrow: it exposes *searches and lookups only*. There is no
 * tool that writes, deletes, changes privileges, sends messages or spends money, so a
 * prompt-injection attempt cannot turn the assistant into an action executor.
 *
 * Tool parameters are normalised and bounded here (server-side), never by the model.
 */
class AssistantContextBuilder
{
    public const INTENT_CENTRE = 'centre_search';

    public const INTENT_SERVICES = 'nearby_services';

    public const INTENT_JOURNEY = 'journey_planning';

    public const INTENT_EXAM = 'examination_info';

    public const INTENT_USAGE = 'platform_usage';

    public const INTENT_FAQS = 'faqs';

    public const INTENT_UNKNOWN = 'unknown';

    private const MAX_CENTRES = 6;

    private const MAX_SERVICES = 6;

    private const MAX_FAQS = 3;

    /** Detect the user's intent using keywords only — no model call, no cost. */
    public function intent(string $question): string
    {
        $q = mb_strtolower($question);

        foreach ([
            self::INTENT_SERVICES => ['hotel', 'hostel', 'lodge', 'stay', 'room', 'food', 'breakfast', 'restaurant',
                'stationery', 'shop', 'xerox', 'photocopy', 'dharamshala', 'where to eat', 'nearby'],
            self::INTENT_CENTRE => ['centre', 'center', 'centres', 'examination centre', 'my centre', 'centre code'],
            self::INTENT_JOURNEY => ['journey', 'travel', 'train', 'bus', 'flight', 'how do i reach', 'reach', 'route',
                'fare', 'ticket', 'distance', 'auto', 'cab', 'taxi', 'metro', 'last mile'],
            self::INTENT_EXAM => ['exam', 'examination', 'admit card', 'hall ticket', 'session', 'nta', 'ssc', 'upsc', 'jee', 'neet'],
            self::INTENT_USAGE => ['how do i use', 'how to use', 'register', 'account', 'login', 'sign in', 'save',
                'itinerary', 'delete my account', 'privacy'],
        ] as $intent => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($q, $kw)) {
                    return $intent;
                }
            }
        }

        return self::INTENT_FAQS;
    }

    /**
     * Retrieve grounded context for a question.
     *
     * @return array{
     *     intent:string,
     *     centres:array<int,array<string,mixed>>,
     *     services:array<int,array<string,mixed>>,
     *     faqs:array<int,array<string,mixed>>,
     *     examinations:array<int,array<string,mixed>>,
     *     city:?string,
     *     tools:array<int,string>,
     *     itinerary:?array<string,mixed>
     * }
     */
    public function retrieve(string $question, ?Itinerary $itinerary = null): array
    {
        $intent = $this->intent($question);
        $city = $this->detectCity($question);
        $tools = [];
        $centres = [];
        $services = [];
        $faqs = [];
        $examinations = [];

        if ($intent === self::INTENT_CENTRE || $intent === self::INTENT_FAQS || $city) {
            $centres = $this->searchCentres($question, $city);
            $tools[] = 'search_centres';
        }

        if ($intent === self::INTENT_SERVICES) {
            $services = $this->searchNearbyServices($question, $city);
            $tools[] = 'search_nearby_services';
        }

        if ($intent === self::INTENT_EXAM) {
            $examinations = $this->searchExaminations($question);
            $tools[] = 'search_examinations';
        }

        $faqs = $this->searchFaqs($question);
        if ($faqs !== []) {
            $tools[] = 'search_faqs';
        }

        return [
            'intent' => $intent,
            'centres' => $centres,
            'services' => $services,
            'faqs' => $faqs,
            'examinations' => $examinations,
            'city' => $city,
            'tools' => array_values(array_unique($tools)),
            'itinerary' => $itinerary ? [
                'title' => $itinerary->title,
                'origin_city' => $itinerary->origin_city,
                'destination_city' => $itinerary->destination_city,
            ] : null,
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public function searchCentres(string $question, ?string $city = null): array
    {
        $query = ExamCentre::query()->with('examination')->where('status', 'active');

        if ($city) {
            $query->whereRaw('LOWER(city) LIKE ?', ['%'.mb_strtolower($city).'%']);
        } else {
            $term = $this->significantTerm($question);
            if ($term) {
                $like = '%'.$term.'%';
                $query->where(function ($q) use ($like) {
                    $q->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(city) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(state) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(centre_code) LIKE ?', [$like]);
                });
            }
        }

        return $query->limit(self::MAX_CENTRES)->get()->map(fn (ExamCentre $c) => [
            'name' => $c->name,
            'centre_code' => $c->centre_code,
            'city' => $c->city,
            'state' => $c->state,
            'examination' => $c->examination?->name,
            'verification_status' => $c->verification_status,
            'is_demo' => (bool) $c->is_demo,
            'has_coordinates' => $c->latitude !== null && $c->longitude !== null,
        ])->all();
    }

    /** @return array<int,array<string,mixed>> */
    public function searchNearbyServices(string $question, ?string $city = null): array
    {
        $q = mb_strtolower($question);
        $category = match (true) {
            str_contains($q, 'stationery'), str_contains($q, 'xerox'), str_contains($q, 'photocopy'), str_contains($q, 'shop') => 'stationery',
            str_contains($q, 'breakfast') => 'breakfast',
            str_contains($q, 'restaurant'), str_contains($q, 'food'), str_contains($q, 'eat') => 'restaurant',
            str_contains($q, 'lodge'), str_contains($q, 'dharamshala') => 'lodge',
            default => 'hotel',
        };

        $query = LocalService::query()->where('status', 'active')->where('category', $category);

        if ($city) {
            $query->whereRaw('LOWER(city) LIKE ?', ['%'.mb_strtolower($city).'%']);
        }

        return $query->limit(self::MAX_SERVICES)->get()->map(fn (LocalService $s) => [
            'name' => $s->name,
            'category' => $s->category,
            'city' => $s->city,
            'price_from' => $s->price_from !== null ? (int) $s->price_from : null,
            'data_status' => $s->data_status ?: DataStatus::Unverified->value,
            'is_demo' => (bool) $s->is_demo,
        ])->all();
    }

    /** @return array<int,array<string,mixed>> */
    public function searchExaminations(string $question): array
    {
        $q = mb_strtolower($question);

        return Examination::query()
            ->where('status', 'active')
            ->where(function ($x) use ($q) {
                foreach (preg_split('/[^a-z0-9]+/', $q) ?: [] as $word) {
                    if (mb_strlen($word) >= 3) {
                        $x->orWhereRaw('LOWER(name) LIKE ?', ['%'.$word.'%']);
                    }
                }
            })
            ->limit(5)
            ->get()
            ->map(fn (Examination $e) => [
                'name' => $e->name,
                'conducting_authority' => $e->conducting_authority,
                'official_url' => $e->official_url,
                'centres_count' => $e->centres()->where('status', 'active')->count(),
            ])->all();
    }

    /** @return array<int,array<string,mixed>> */
    public function searchFaqs(string $question): array
    {
        $words = array_values(array_filter(
            preg_split('/[^a-z0-9]+/u', mb_strtolower($question)) ?: [],
            fn ($w) => mb_strlen($w) > 2,
        ));

        if ($words === []) {
            return [];
        }

        return Faq::query()
            ->where('is_published', true)
            ->get()
            ->map(function (Faq $f) use ($words) {
                $haystack = mb_strtolower($f->question.' '.$f->answer);
                $score = 0;
                foreach ($words as $w) {
                    if (str_contains($haystack, $w)) {
                        $score++;
                    }
                }

                return ['faq' => $f, 'score' => $score];
            })
            ->filter(fn ($r) => $r['score'] >= 2)
            ->sortByDesc('score')
            ->take(self::MAX_FAQS)
            ->map(fn ($r) => [
                'question' => $r['faq']->question,
                'answer' => $r['faq']->answer,
                'category' => $r['faq']->category,
                'source_url' => $r['faq']->source_url,
            ])->values()->all();
    }

    public function detectCity(string $question): ?string
    {
        $q = mb_strtolower($question);

        foreach (CityDirectory::names() as $city) {
            if (str_contains($q, mb_strtolower($city))) {
                return $city;
            }
        }

        // Fall back to cities that actually have centres in the database.
        foreach (ExamCentre::query()->where('status', 'active')->distinct()->limit(400)->pluck('city') as $city) {
            $city = (string) $city;
            if ($city !== '' && str_contains($q, mb_strtolower($city))) {
                return $city;
            }
        }

        return null;
    }

    /** Longest alphabetic token, used as a crude but predictable search term. */
    private function significantTerm(string $question): ?string
    {
        $words = array_values(array_filter(
            preg_split('/[^a-z0-9]+/u', mb_strtolower($question)) ?: [],
            fn ($w) => mb_strlen($w) > 3 && ! in_array($w, [
                'centre', 'center', 'centres', 'centers', 'exam', 'exams', 'examination', 'information',
                'where', 'what', 'which', 'list', 'find', 'near', 'please', 'tell',
            ], true),
        ));

        if ($words === []) {
            return null;
        }

        usort($words, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return $words[0];
    }
}
