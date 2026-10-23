<?php

namespace App\Integrations\Support;

/**
 * Result of a route calculation. When the provider is unavailable, `available`
 * is false and callers must show a text/address fallback instead of a route.
 */
final readonly class RouteResult
{
    public function __construct(
        public bool $available,
        public ?int $distanceMeters = null,
        public ?int $durationSeconds = null,
        public ?string $mode = null,
        public ?string $encodedPolyline = null,
        public ?string $providerName = null,
        public ?string $sourceUrl = null,
        public ?string $retrievedAt = null,
        public ?string $error = null,
        public array $steps = [],
    ) {}

    public static function unavailable(string $reason, ?string $providerName = null): self
    {
        return new self(available: false, providerName: $providerName, retrievedAt: now()->toIso8601String(), error: $reason);
    }

    public function distanceKm(): ?float
    {
        return $this->distanceMeters !== null ? round($this->distanceMeters / 1000, 1) : null;
    }

    public function durationMinutes(): ?int
    {
        return $this->durationSeconds !== null ? (int) round($this->durationSeconds / 60) : null;
    }

    public function toArray(): array
    {
        return [
            'available' => $this->available,
            'distance_meters' => $this->distanceMeters,
            'duration_seconds' => $this->durationSeconds,
            'mode' => $this->mode,
            'encoded_polyline' => $this->encodedPolyline,
            'provider_name' => $this->providerName,
            'source_url' => $this->sourceUrl,
            'retrieved_at' => $this->retrievedAt,
            'error' => $this->error,
            'steps' => $this->steps,
        ];
    }
}
