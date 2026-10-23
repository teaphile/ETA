<?php

namespace App\Contracts;

use App\Integrations\Support\ProviderResult;
use App\Integrations\Support\TripRequest;

/**
 * Intercity transport information (rail / bus / flight / personal vehicle).
 *
 * There is no public, openly licensed live Indian rail/bus/flight fare or seat
 * availability API. Authorised implementations may return observed fares; every
 * other implementation must return clearly labelled estimates or mark the price
 * unavailable and point to the official booking site.
 */
interface TransportProvider
{
    public function name(): string;

    public function isAvailable(): bool;

    /**
     * @return ProviderResult data = array<int,\App\Integrations\Support\TravelOption>
     */
    public function plan(TripRequest $request): ProviderResult;
}
