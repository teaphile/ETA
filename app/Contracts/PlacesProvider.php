<?php

namespace App\Contracts;

use App\Integrations\Support\ProviderResult;

/**
 * Local place discovery (hotels, lodges, restaurants, stationery shops, ...).
 *
 * Implementations must respect the source's terms of use, including attribution and
 * caching limits. Results are returned inside a ProviderResult so the caller can
 * distinguish "no results" from "provider unavailable".
 */
interface PlacesProvider
{
    public function name(): string;

    public function isAvailable(): bool;

    /**
     * @param  array{
     *     lat?: float|null, lng?: float|null, city?: string|null,
     *     category?: string|null, radius_m?: int|null, limit?: int|null, keyword?: string|null
     * }  $query
     * @return ProviderResult data = array<int,\App\Integrations\Support\PlaceResult>
     */
    public function search(array $query): ProviderResult;
}
