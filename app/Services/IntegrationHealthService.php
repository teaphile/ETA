<?php

namespace App\Services;

use App\Contracts\AIProvider;
use App\Contracts\DirectionsProvider;
use App\Contracts\PlacesProvider;
use App\Contracts\TransportProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Records non-sensitive operational metadata about external integrations so an
 * administrator can see error rates and latency without reading application logs.
 *
 * Never records API keys, request bodies or personal data — only provider, operation,
 * success flag, HTTP status, latency and a coarse error category.
 */
class IntegrationHealthService
{
    public function record(
        string $provider,
        string $operation,
        bool $ok,
        float $startedAtMicrotime,
        ?int $httpStatus = null,
        ?string $errorCategory = null,
    ): void {
        try {
            DB::table('integration_events')->insert([
                'provider' => $provider,
                'operation' => $operation,
                'success' => $ok,
                'http_status' => $httpStatus,
                'latency_ms' => (int) round((microtime(true) - $startedAtMicrotime) * 1000),
                'error_category' => $ok ? null : ($errorCategory ?? 'provider_error'),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Health telemetry must never break a user-facing request.
            Log::debug('Could not record integration health event', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Aggregate per-provider statistics for the admin health screen.
     *
     * @return Collection<int,object>
     */
    public function summary(int $days = 7): Collection
    {
        return DB::table('integration_events')
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('provider, operation, count(*) as calls, sum(case when success = 1 then 1 else 0 end) as ok_calls, avg(latency_ms) as avg_latency_ms')
            ->groupBy('provider', 'operation')
            ->orderBy('provider')
            ->get();
    }

    /**
     * Configured-but-unverified integrations, for the honest status panel.
     *
     * @return array<int,array{key:string,label:string,provider:string,available:bool,note:string}>
     */
    public function configuration(): array
    {
        return [
            [
                'key' => 'maps',
                'label' => 'Google Maps (directions & geocoding)',
                'provider' => app(DirectionsProvider::class)->name(),
                'available' => app(DirectionsProvider::class)->isAvailable(),
                'note' => 'Without a server key the app shows addresses, text fallbacks and keyless Google Maps links.',
            ],
            [
                'key' => 'places',
                'label' => 'Local services (places)',
                'provider' => app(PlacesProvider::class)->name(),
                'available' => app(PlacesProvider::class)->isAvailable(),
                'note' => 'Curated admin listings always work; live provider search needs a Places server key.',
            ],
            [
                'key' => 'transport',
                'label' => 'Transport data',
                'provider' => app(TransportProvider::class)->name(),
                'available' => app(TransportProvider::class)->isAvailable(),
                'note' => 'Default provider returns disclosed distance-based estimates plus official booking links. No live Indian fare API is publicly licensed.',
            ],
            [
                'key' => 'ai',
                'label' => 'AI assistant',
                'provider' => app(AIProvider::class)->name(),
                'available' => app(AIProvider::class)->isAvailable(),
                'note' => 'The grounded FAQ/database answerer always works; an LLM provider may answer the long tail of questions.',
            ],
        ];
    }
}
