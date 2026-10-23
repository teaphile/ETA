<?php

namespace App\Services;

use App\Models\ExamCentre;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Examination-centre search: input normalisation, query construction and outcome logging.
 *
 * Kept out of the controller so the normalisation and filter behaviour can be unit
 * tested directly, and so the search screen, the chatbot and the admin panel all use
 * one definition of "search".
 */
class CentreSearchService
{
    public const PER_PAGE = 12;

    private const MAX_TERM_LENGTH = 100;

    public function __construct(private readonly SearchLogger $logger) {}

    /**
     * Normalise raw request input: trim, collapse whitespace, drop control characters,
     * bound the length and discard empty values.
     *
     * @param  array<string,mixed>  $input
     * @return array{exam:?int,session:?int,state:?string,city:?string,code:?string,q:?string,sort:string}
     */
    public function normalise(array $input): array
    {
        $sort = in_array(($input['sort'] ?? null), ['city', 'name', 'recent'], true) ? $input['sort'] : 'city';

        return [
            'exam' => $this->positiveInt($input['exam'] ?? null),
            'session' => $this->positiveInt($input['session'] ?? null),
            'state' => $this->text($input['state'] ?? null),
            'city' => $this->text($input['city'] ?? null),
            'code' => $this->text($input['code'] ?? null),
            'q' => $this->text($input['q'] ?? null),
            'sort' => $sort,
        ];
    }

    public function text(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = (string) $value;
        // Normalise NBSP and other unicode spaces, then collapse runs of whitespace.
        $value = preg_replace('/[\p{Z}\s]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? $value;
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, self::MAX_TERM_LENGTH);
    }

    public function positiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    /**
     * @param  array<string,mixed>  $normalised  output of normalise()
     */
    public function query(array $normalised): LengthAwarePaginator
    {
        $query = ExamCentre::query()->active()->with(['examination:id,name', 'session:id,name']);

        if ($normalised['exam']) {
            $query->where('examination_id', $normalised['exam']);
        }

        if ($normalised['session']) {
            $query->where('exam_session_id', $normalised['session']);
        }

        if ($normalised['state']) {
            $query->whereRaw('LOWER(state) LIKE ?', ['%'.mb_strtolower($normalised['state']).'%']);
        }

        if ($normalised['city']) {
            $query->whereRaw('LOWER(city) LIKE ?', ['%'.mb_strtolower($normalised['city']).'%']);
        }

        if ($normalised['code']) {
            $query->whereRaw('LOWER(centre_code) LIKE ?', ['%'.mb_strtolower($normalised['code']).'%']);
        }

        if ($normalised['q']) {
            $like = '%'.mb_strtolower($normalised['q']).'%';
            $query->where(function ($x) use ($like) {
                $x->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(address) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(city) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(district) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(pin_code) LIKE ?', [$like]);
            });
        }

        match ($normalised['sort']) {
            'name' => $query->orderByRaw('LOWER(name) asc'),
            'recent' => $query->latest('id'),
            default => $query->orderByRaw('LOWER(city) asc')->orderByRaw('LOWER(name) asc'),
        };

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * Record an anonymised search outcome so the admin panel can report no-result rates.
     * The stored summary is bounded and contains no personal data.
     */
    public function log(array $normalised, int $results, ?int $userId = null): void
    {
        $this->logger->log('centre', $normalised, $results, $userId);
    }

    /** @return array<int,string> */
    public function availableStates(): array
    {
        return ExamCentre::query()->active()->distinct()->orderBy('state')->pluck('state')
            ->filter()->values()->all();
    }

    /** @return array<int,string> */
    public function availableCities(): array
    {
        return ExamCentre::query()->active()->distinct()->orderBy('city')->pluck('city')
            ->filter()->values()->all();
    }
}
