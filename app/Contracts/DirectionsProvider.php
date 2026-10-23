<?php

namespace App\Contracts;

use App\Integrations\Support\ProviderResult;
use App\Integrations\Support\RouteResult;

/**
 * Route calculation and geocoding.
 *
 * A failed route request must be surfaced as unavailable — never replaced with an
 * invented route. Callers keep the user's inputs and offer a retry plus a supported
 * external link.
 */
interface DirectionsProvider
{
    public function name(): string;

    public function isAvailable(): bool;

    /**
     * Convert a free-text address or place name into coordinates.
     *
     * @return ProviderResult data = array{lat: float, lng: float, formatted_address: ?string}
     */
    public function geocode(string $query): ProviderResult;

    /**
     * @param  string  $mode  driving | walking | transit | bicycling
     */
    public function route(
        float $originLat,
        float $originLng,
        float $destLat,
        float $destLng,
        string $mode = 'driving',
    ): RouteResult;
}
