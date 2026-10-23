<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Minimal, privacy-conscious search analytics.
 *
 * Only an anonymised filter summary, the result count and an optional user id are
 * stored. Filters are bounded and stripped of anything that could identify a person
 * (no IP address, no free-text longer than the column allows). Rows are pruned by
 * `data:prune` after `exam_travel.retention.search_log_days`.
 */
class SearchLogger
{
    public const TYPES = ['centre', 'place', 'journey', 'exam'];

    /**
     * @param  array<string,mixed>  $filters
     */
    public function log(string $type, array $filters, int $results, ?int $userId = null): void
    {
        try {
            DB::table('search_logs')->insert([
                'type' => in_array($type, self::TYPES, true) ? $type : 'centre',
                'user_id' => $userId,
                'query_summary' => mb_substr(
                    json_encode(array_filter($filters, fn ($v) => $v !== null && $v !== '')) ?: '{}',
                    0,
                    250,
                ),
                'results_count' => max(0, $results),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Analytics must never break a user-facing search.
            Log::debug('Search log write failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @return array{total:int,no_results:int,no_result_rate:float,window_days:int}
     */
    public function outcomeSummary(int $days = 30): array
    {
        $since = now()->subDays($days);

        $total = DB::table('search_logs')->where('created_at', '>=', $since)->count();
        $noResults = DB::table('search_logs')->where('created_at', '>=', $since)->where('results_count', 0)->count();

        return [
            'total' => $total,
            'no_results' => $noResults,
            'no_result_rate' => $total > 0 ? round($noResults / $total * 100, 1) : 0.0,
            'window_days' => $days,
        ];
    }

    /** @return Collection<int,object> */
    public function byType(int $days = 30)
    {
        return DB::table('search_logs')
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('type, count(*) as total, sum(case when results_count = 0 then 1 else 0 end) as empty')
            ->groupBy('type')
            ->orderByDesc('total')
            ->get();
    }
}
