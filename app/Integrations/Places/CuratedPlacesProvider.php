<?php

namespace App\Integrations\Places;

use App\Contracts\PlacesProvider;
use App\Integrations\Support\CityDirectory;
use App\Integrations\Support\DataStatus;
use App\Integrations\Support\PlaceResult;
use App\Integrations\Support\ProviderResult;
use App\Models\LocalService;
use Illuminate\Support\Facades\DB;

/**
 * Administrator-curated local listings stored in `local_services`.
 *
 * Always available (no external key needed) and therefore the safe default. Each
 * listing carries its own provenance and last-verified timestamp, and demo rows stay
 * flagged so they can never be mistaken for real businesses.
 */
class CuratedPlacesProvider implements PlacesProvider
{
    public function name(): string
    {
        return 'curated';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function search(array $query): ProviderResult
    {
        $lat = isset($query['lat']) ? (float) $query['lat'] : null;
        $lng = isset($query['lng']) ? (float) $query['lng'] : null;
        $limit = (int) ($query['limit'] ?? 12);
        $radiusMeters = (int) ($query['radius_m'] ?? config('services.places.radius_default_m', 3000));

        $builder = LocalService::query()
            ->where('status', 'active')
            ->when($query['category'] ?? null, fn ($q, $cat) => $q->where('category', $cat))
            ->when($query['city'] ?? null, function ($q, $city) {
                $like = '%'.mb_strtolower(trim((string) $city)).'%';
                $q->whereRaw('LOWER(city) LIKE ?', [$like]);
            })
            ->when($query['keyword'] ?? null, function ($q, $kw) {
                $like = '%'.mb_strtolower(trim((string) $kw)).'%';
                $q->where(function ($x) use ($like) {
                    $x->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(address) LIKE ?', [$like]);
                });
            })
            ->when($query['exam_centre_id'] ?? null, fn ($q, $id) => $q->where('exam_centre_id', $id));

        $rows = $builder->orderBy('name')->limit($limit)->get();

        $results = $rows->map(function (LocalService $m) use ($lat, $lng, $radiusMeters) {
            $distance = null;

            if ($lat !== null && $lng !== null && $m->latitude !== null && $m->longitude !== null) {
                $distance = round(CityDirectory::haversineKm($lat, $lng, (float) $m->latitude, (float) $m->longitude), 1);

                if ($distance * 1000 > $radiusMeters) {
                    return null;
                }
            }

            return PlaceResult::fromModel($m, $distance);
        })->filter()->values()->all();

        $results = $this->sortByDistance($results);

        if ($results === []) {
            return ProviderResult::unavailable(
                'No curated listings match this search. Live third-party place search is '
                .(filled(config('services.places.server_key')) ? 'configured and returned nothing.' : 'not configured (no server key).'),
                'Curated listings (admin maintained)',
            );
        }

        return ProviderResult::ok(
            data: $results,
            sourceName: 'Curated listings (admin maintained)',
            sourceUrl: null,
            isEstimated: false,
            meta: ['count' => count($results)],
        );
    }

    /** @param array<int,PlaceResult> $results */
    private function sortByDistance(array $results): array
    {
        usort($results, function (PlaceResult $a, PlaceResult $b) {
            if ($a->distanceKm === null && $b->distanceKm === null) {
                return strcmp($a->name, $b->name);
            }
            if ($a->distanceKm === null) {
                return 1;
            }
            if ($b->distanceKm === null) {
                return -1;
            }

            return $a->distanceKm <=> $b->distanceKm;
        });

        return $results;
    }

    /** Freshness report used by the admin data-quality screen. */
    public function freshness(): array
    {
        return [
            'total' => LocalService::where('status', 'active')->count(),
            'unverified' => LocalService::where('status', 'active')->where('data_status', DataStatus::Unverified->value)->count(),
            'demo' => LocalService::where('status', 'active')->where('is_demo', true)->count(),
            'stale' => LocalService::where('status', 'active')
                ->where(fn ($q) => $q->whereNull('last_verified_at')->orWhere('last_verified_at', '<', now()->subMonths(6)))
                ->count(),
            'providers' => DB::table('local_services')->select('source_name')->distinct()->pluck('source_name')->all(),
        ];
    }
}
