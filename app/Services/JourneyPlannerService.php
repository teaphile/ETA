<?php

namespace App\Services;

use App\Contracts\DirectionsProvider;
use App\Contracts\TransportProvider;
use App\Integrations\Support\CityDirectory;
use App\Integrations\Support\DataStatus;
use App\Integrations\Support\JourneyPlan;
use App\Integrations\Support\TravelOption;
use App\Integrations\Support\TripRequest;
use App\Integrations\Transport\LastMileEstimator;
use App\Models\ExamCentre;
use Carbon\CarbonImmutable;

/**
 * Orchestrates journey planning: resolves locations, asks the configured transport
 * provider for intercity options, then adds last-mile options to the centre.
 *
 * Deliberately thin and provider-agnostic. When locations cannot be resolved, or the
 * provider has nothing, it returns an explicit limitation instead of a fabricated
 * plan.
 */
class JourneyPlannerService
{
    public function __construct(
        private readonly TransportProvider $transport,
        private readonly DirectionsProvider $directions,
        private readonly LastMileEstimator $lastMile,
        private readonly IntegrationHealthService $health,
    ) {}

    /**
     * @param  array{
     *     origin_city:string, origin_address?:?string, origin_lat?:?float, origin_lng?:?float,
     *     departure_date?:?string, budget?:?int, modes?:array<int,string>
     * }  $input
     */
    public function plan(ExamCentre $centre, array $input): JourneyPlan
    {
        $notes = [];
        $limitations = [];

        [$originLat, $originLng, $originNote] = $this->resolveOrigin($input);
        if ($originNote) {
            $notes['origin'] = $originNote;
        }

        [$destLat, $destLng, $destNote] = $this->resolveDestination($centre);
        if ($destNote) {
            $notes['destination'] = $destNote;
        }

        if ($originLat === null || $originLng === null || $destLat === null || $destLng === null) {
            $reason = 'Distance-based planning is unavailable because we could not resolve verified coordinates for '
                .($originLat === null ? 'your starting point' : 'the exam centre')
                .'. We do not guess locations — use the official booking links below and the address shown on the centre page.';

            return JourneyPlan::unavailable($reason, $notes);
        }

        $departure = null;
        if (! empty($input['departure_date'])) {
            try {
                $departure = CarbonImmutable::parse($input['departure_date'], TripRequest::TIMEZONE)->startOfDay();
            } catch (\Throwable) {
                $limitations['departure_date'] = 'The departure date could not be read, so date-specific advisories are omitted.';
            }
        }

        $request = new TripRequest(
            originCity: (string) $input['origin_city'],
            destinationCity: (string) $centre->city,
            originLat: $originLat,
            originLng: $originLng,
            destinationLat: $destLat,
            destinationLng: $destLng,
            departureAt: $departure,
            budget: isset($input['budget']) ? (int) $input['budget'] : null,
            modes: array_values(array_intersect(
                $input['modes'] ?? array_keys(config('exam_travel.travel_modes')),
                ['train', 'bus', 'flight', 'car'],
            )),
            examCentreId: (int) $centre->id,
        );

        $result = $this->transport->plan($request);
        $options = is_array($result->data) ? $result->data : [];

        if (! $result->isAvailable()) {
            return new JourneyPlan(
                intercityStatus: DataStatus::Unavailable->value,
                providerName: $result->sourceName,
                providerSourceUrl: $result->sourceUrl,
                resolutionNotes: $notes,
                limitations: $limitations,
                unavailableReason: $result->error,
                retrievedAt: $result->retrievedAt,
            );
        }

        if ($options === []) {
            $limitations['modes'] = 'No travel mode was selected. Tick at least one mode to compare options.';
        }

        // Route distance for the map/last-mile step. A failed route is reported, not faked.
        $route = $this->directions->route($originLat, $originLng, $destLat, $destLng, 'driving');
        if (! $route->available) {
            $limitations['route'] = $route->error
                ?? 'A live road route could not be retrieved; distances below are straight-line based estimates.';
        }

        $straightKm = (int) round(CityDirectory::haversineKm($originLat, $originLng, $destLat, $destLng));
        $roadKm = $route->available && $route->distanceMeters
            ? (int) round($route->distanceMeters / 1000)
            : CityDirectory::roadKmApprox($straightKm);

        if ($options !== [] && $result->isEstimated) {
            $limitations['pricing'] = 'All prices below are disclosed distance-based planning ranges. No public, permitted API publishes live Indian rail, bus or air fares, and this platform never simulates them.';
        }

        // Last mile: arrival point → centre. Uses the city reference point unless the
        // caller supplied a specific arrival location.
        $lastMileKm = null;
        $lastMileOptions = [];

        if ($centre->hasCoordinates()) {
            $reference = CityDirectory::coords($centre->city);
            if ($reference) {
                $lastMileKm = round(CityDirectory::haversineKm($reference[0], $reference[1], (float) $centre->latitude, (float) $centre->longitude), 1);
                $lastMileOptions = $this->lastMile->options($lastMileKm, $centre->mapsDirectionsUrl());
            } else {
                $limitations['last_mile'] = 'We do not hold a reference point for '.$centre->city.', so last-mile distances are not estimated. Open the map for live local options.';
            }
        }

        return new JourneyPlan(
            intercityOptions: $options,
            lastMileOptions: $lastMileOptions,
            intercityStatus: $result->status,
            providerName: $result->sourceName ?? $this->transport->name(),
            providerSourceUrl: $result->sourceUrl,
            isEstimated: $result->isEstimated,
            straightKm: $straightKm,
            roadKm: $roadKm,
            lastMileKm: $lastMileKm,
            resolutionNotes: $notes,
            limitations: $limitations,
            retrievedAt: $result->retrievedAt,
        );
    }

    /** @return array{0:?float,1:?float,2:?string} */
    private function resolveOrigin(array $input): array
    {
        // 1. Explicit coordinates (browser geolocation, with consent) win.
        if (isset($input['origin_lat'], $input['origin_lng'])
            && is_numeric($input['origin_lat']) && is_numeric($input['origin_lng'])) {
            return [(float) $input['origin_lat'], (float) $input['origin_lng'], 'Using the shared location you provided.'];
        }

        // 2. Known reference city — no external call, no cost.
        if ($coords = CityDirectory::coords($input['origin_city'] ?? null)) {
            return [$coords[0], $coords[1], null];
        }

        // 3. Free-text address via the configured geocoder (if any).
        $address = trim((string) ($input['origin_address'] ?? ''));

        if ($address !== '') {
            $geo = $this->directions->geocode($address);

            if ($geo->isAvailable() && is_array($geo->data) && isset($geo->data['lat'], $geo->data['lng'])) {
                return [(float) $geo->data['lat'], (float) $geo->data['lng'], 'Starting point resolved from the address you typed.'];
            }
        }

        return [null, null, 'We do not hold reference coordinates for "'.e((string) ($input['origin_city'] ?? '')).'". Pick a listed city, share your location, or type a fuller address.'];
    }

    /** @return array{0:?float,1:?float,2:?string} */
    private function resolveDestination(ExamCentre $centre): array
    {
        if ($centre->hasCoordinates()) {
            return [(float) $centre->latitude, (float) $centre->longitude, null];
        }

        if ($coords = CityDirectory::coords($centre->city)) {
            return [$coords[0], $coords[1], 'This centre has no verified coordinates yet, so '.$centre->city.' city centre is used as an approximation. Confirm the exact location from your admit card.'];
        }

        $geo = $this->directions->geocode($centre->fullAddress());
        if ($geo->isAvailable() && is_array($geo->data) && isset($geo->data['lat'], $geo->data['lng'])) {
            return [(float) $geo->data['lat'], (float) $geo->data['lng'], 'Destination coordinates resolved from the recorded address (not yet admin-verified).'];
        }

        return [null, null, 'No verified coordinates or reference city are available for this centre.'];
    }

    public static function modesFor(array $requested): array
    {
        $allowed = ['train', 'bus', 'flight', 'car'];
        $modes = array_values(array_intersect($requested, $allowed));

        return $modes === [] ? $allowed : $modes;
    }

    /** @return array<int,TravelOption> */
    public function lastMileFor(float $km): array
    {
        return $this->lastMile->options($km);
    }
}
