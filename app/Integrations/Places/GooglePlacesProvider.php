<?php

namespace App\Integrations\Places;

use App\Contracts\PlacesProvider;
use App\Integrations\Support\CityDirectory;
use App\Integrations\Support\DataStatus;
use App\Integrations\Support\PlaceResult;
use App\Integrations\Support\ProviderResult;
use App\Services\IntegrationHealthService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Places API (Nearby Search) adapter.
 *
 * Requires GOOGLE_PLACES_SERVER_KEY (or GOOGLE_MAPS_SERVER_KEY). Without a key it
 * reports itself unavailable instead of failing silently.
 *
 * Google's terms require attribution and limit how long place data may be cached,
 * so results are cached only for `services.places.cache_seconds` (default 300s) and
 * the UI always shows the "Google" attribution and retrieval time. Ratings shown are
 * provider ratings, never a safety assessment.
 */
class GooglePlacesProvider implements PlacesProvider
{
    /** Maps our internal categories onto Google place search keywords. */
    private const KEYWORDS = [
        'hotel' => 'hotel lodge guest house',
        'lodge' => 'lodge guest house dharamshala',
        'waiting_lounge' => 'waiting room lounge',
        'restaurant' => 'restaurant',
        'breakfast' => 'breakfast restaurant tiffin',
        'stationery' => 'stationery shop printing photocopy',
        'other' => 'convenience store',
    ];

    public function __construct(private readonly IntegrationHealthService $health) {}

    public function name(): string
    {
        return 'google';
    }

    public function isAvailable(): bool
    {
        return filled(config('services.places.server_key'));
    }

    public function search(array $query): ProviderResult
    {
        if (! $this->isAvailable()) {
            return ProviderResult::unavailable(
                'Live place search is not configured. Add GOOGLE_PLACES_SERVER_KEY to enable it; curated listings still work.',
                'Google Places API',
            );
        }

        if (! isset($query['lat'], $query['lng']) || $query['lat'] === null || $query['lng'] === null) {
            return ProviderResult::unavailable(
                'Live place search needs verified coordinates. This centre or location has no verified coordinates yet, so we do not guess.',
                'Google Places API',
            );
        }

        $category = (string) ($query['category'] ?? 'other');
        $keyword = (string) ($query['keyword'] ?? self::KEYWORDS[$category] ?? self::KEYWORDS['other']);
        $radius = (int) ($query['radius_m'] ?? config('services.places.radius_default_m', 3000));
        $limit = min(20, (int) ($query['limit'] ?? config('services.places.max_results', 10)));

        $cacheKey = 'places:'.md5(implode('|', [$query['lat'], $query['lng'], $category, $keyword, $radius, $limit]));
        $ttl = (int) config('services.places.cache_seconds', 300);

        try {
            $started = microtime(true);
            $payload = Cache::remember($cacheKey, $ttl, function () use ($query, $keyword, $radius, $started) {
                $res = Http::timeout((int) config('services.places.timeout', 8))
                    ->retry(2, 250, throw: false)
                    ->get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
                        'location' => $query['lat'].','.$query['lng'],
                        'radius' => $radius,
                        'keyword' => $keyword,
                        'language' => config('services.google_maps.language', 'en'),
                        'key' => config('services.places.server_key'),
                    ]);

                $this->health->record('google', 'places.nearby', $res->successful(), $started, $res->status());

                return ['http' => $res->status(), 'body' => $res->json(), 'ok' => $res->successful()];
            });

            if (! ($payload['ok'] ?? false)) {
                return ProviderResult::failed('Place search provider returned HTTP '.($payload['http'] ?? '?').'.', 'Google Places API');
            }

            $status = $payload['body']['status'] ?? 'UNKNOWN';
            if (! in_array($status, ['OK', 'ZERO_RESULTS'], true)) {
                Log::warning('Places provider non-OK status', ['status' => $status]);

                return ProviderResult::failed('Place search rejected the request (provider status '.e($status).').', 'Google Places API');
            }

            $results = collect($payload['body']['results'] ?? [])->take($limit)->map(function (array $p) use ($query) {
                $lat = (float) ($p['geometry']['location']['lat'] ?? 0);
                $lng = (float) ($p['geometry']['location']['lng'] ?? 0);
                $distance = ($lat && $lng)
                    ? round(CityDirectory::haversineKm((float) $query['lat'], (float) $query['lng'], $lat, $lng), 1)
                    : null;

                return new PlaceResult(
                    name: (string) ($p['name'] ?? 'Unnamed place'),
                    category: (string) ($query['category'] ?? 'other'),
                    address: $p['vicinity'] ?? null,
                    latitude: $lat ?: null,
                    longitude: $lng ?: null,
                    distanceKm: $distance,
                    rating: isset($p['rating']) ? (float) $p['rating'] : null,
                    reviewsCount: isset($p['user_ratings_total']) ? (int) $p['user_ratings_total'] : null,
                    priceFrom: null,
                    sourceName: 'Google Places API',
                    sourceUrl: 'https://www.google.com/maps',
                    providerReference: $p['place_id'] ?? null,
                    dataStatus: DataStatus::ThirdParty->value,
                    retrievedAt: now()->toIso8601String(),
                    navigationUrl: isset($p['place_id']) ? 'https://www.google.com/maps/place/?q=place_id:'.$p['place_id'] : null,
                    errorOrLimitation: 'Rating is a public provider rating, not a verified safety assessment. Prices and vacancies are not provided by this API.',
                );
            })->all();

            if ($results === []) {
                return ProviderResult::unavailable('The place provider returned no results near that point.', 'Google Places API');
            }

            return ProviderResult::ok(
                data: $results,
                sourceName: 'Google Places API',
                sourceUrl: 'https://www.google.com/maps',
                isEstimated: false,
                meta: ['attribution' => 'Google', 'cached_seconds' => $ttl, 'count' => count($results)],
            );
        } catch (\Throwable $e) {
            $this->health->record('google', 'places.nearby', false, microtime(true), null, 'exception');
            Log::warning('Places request failed', ['provider' => 'google', 'error' => $e->getMessage()]);

            return ProviderResult::failed('Place search failed (timeout or network error).', 'Google Places API');
        }
    }
}
