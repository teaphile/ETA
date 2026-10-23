<?php

namespace App\Integrations\Directions;

use App\Contracts\DirectionsProvider;
use App\Integrations\Support\ProviderResult;
use App\Integrations\Support\RouteResult;
use App\Services\IntegrationHealthService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Maps Platform directions + geocoding.
 *
 * Requires GOOGLE_MAPS_SERVER_KEY (restrict the key by API and by server IP in the
 * Google Cloud console). Without a key every method returns a clean *unavailable*
 * result — the application then renders an address/text fallback and a keyless
 * Google Maps deep link. It never substitutes a fabricated route.
 */
class GoogleDirectionsProvider implements DirectionsProvider
{
    public function __construct(private readonly IntegrationHealthService $health) {}

    public function name(): string
    {
        return 'google';
    }

    public function isAvailable(): bool
    {
        return filled(config('services.google_maps.server_key'));
    }

    public function geocode(string $query): ProviderResult
    {
        if (! $this->isAvailable()) {
            return ProviderResult::unavailable(
                'Geocoding is not configured. Add GOOGLE_MAPS_SERVER_KEY to resolve addresses automatically; for now we show the address as text.',
                'Google Geocoding API',
            );
        }

        try {
            $started = microtime(true);
            $res = Http::timeout((int) config('services.google_maps.timeout', 8))
                ->retry(2, 250, throw: false)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $query,
                    'region' => config('services.google_maps.region', 'IN'),
                    'language' => config('services.google_maps.language', 'en'),
                    'key' => config('services.google_maps.server_key'),
                ]);
            $this->health->record('google', 'geocode', $res->successful(), $started, $res->status());

            if (! $res->successful()) {
                return ProviderResult::failed('Geocoding provider returned HTTP '.$res->status().'.', 'Google Geocoding API');
            }

            $status = $res->json('status');
            if ($status !== 'OK' || empty($res->json('results.0'))) {
                return ProviderResult::unavailable(
                    'No coordinates found for that address (provider status: '.e($status ?? 'unknown').').',
                    'Google Geocoding API',
                );
            }

            $first = $res->json('results.0');

            return ProviderResult::ok([
                'lat' => (float) $first['geometry']['location']['lat'],
                'lng' => (float) $first['geometry']['location']['lng'],
                'formatted_address' => $first['formatted_address'] ?? null,
            ], 'Google Geocoding API', 'https://maps.google.com');
        } catch (\Throwable $e) {
            $this->health->record('google', 'geocode', false, microtime(true), null, 'exception');
            Log::warning('Geocoding request failed', ['provider' => 'google', 'error' => $e->getMessage()]);

            return ProviderResult::failed('Geocoding request failed (timeout or network error).', 'Google Geocoding API');
        }
    }

    public function route(
        float $originLat,
        float $originLng,
        float $destLat,
        float $destLng,
        string $mode = 'driving',
    ): RouteResult {
        if (! $this->isAvailable()) {
            return RouteResult::unavailable(
                'Turn-by-turn routing is not configured. Open the location in Google Maps for live directions.',
                'Google Directions API',
            );
        }

        try {
            $started = microtime(true);
            $res = Http::timeout((int) config('services.google_maps.timeout', 8))
                ->retry(2, 250, throw: false)
                ->get('https://maps.googleapis.com/maps/api/directions/json', [
                    'origin' => $originLat.','.$originLng,
                    'destination' => $destLat.','.$destLng,
                    'mode' => $mode,
                    'region' => config('services.google_maps.region', 'IN'),
                    'key' => config('services.google_maps.server_key'),
                ]);
            $this->health->record('google', 'directions', $res->successful(), $started, $res->status());

            if (! $res->successful()) {
                return RouteResult::unavailable('Routing provider returned HTTP '.$res->status().'.', 'Google Directions API');
            }

            $route = $res->json('routes.0');
            $leg = $route['legs'][0] ?? null;

            if (! $route || ! $leg) {
                return RouteResult::unavailable(
                    'No route found for that mode (provider status: '.e($res->json('status') ?? 'unknown').').',
                    'Google Directions API',
                );
            }

            return new RouteResult(
                available: true,
                distanceMeters: (int) ($leg['distance']['value'] ?? 0) ?: null,
                durationSeconds: (int) ($leg['duration']['value'] ?? 0) ?: null,
                mode: $mode,
                encodedPolyline: $route['overview_polyline']['points'] ?? null,
                providerName: 'Google Directions API',
                sourceUrl: 'https://maps.google.com',
                retrievedAt: now()->toIso8601String(),
                steps: $this->plainTextSteps($leg['steps'] ?? []),
            );
        } catch (\Throwable $e) {
            $this->health->record('google', 'directions', false, microtime(true), null, 'exception');
            Log::warning('Directions request failed', ['provider' => 'google', 'error' => $e->getMessage()]);

            return RouteResult::unavailable('Routing request failed (timeout or network error).', 'Google Directions API');
        }
    }

    /**
     * Text directions so the information is never map-only (accessibility + low bandwidth).
     *
     * @param  array<int,array<string,mixed>>  $steps
     * @return array<int,array{instruction:string,distance:string,duration:string}>
     */
    private function plainTextSteps(array $steps): array
    {
        return array_map(fn ($s) => [
            'instruction' => strip_tags((string) ($s['html_instructions'] ?? '')),
            'distance' => $s['distance']['text'] ?? '',
            'duration' => $s['duration']['text'] ?? '',
        ], array_slice($steps, 0, 25));
    }
}
