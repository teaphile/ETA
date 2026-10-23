<?php

namespace App\Integrations\Transport;

use App\Contracts\TransportProvider;
use App\Integrations\Support\CityDirectory;
use App\Integrations\Support\DataStatus;
use App\Integrations\Support\ProviderResult;
use App\Integrations\Support\TravelOption;
use App\Integrations\Support\TripRequest;

/**
 * Default transport provider.
 *
 * India has no public, openly licensed API for live rail/bus/flight fares or seat
 * availability (see docs/API_SETUP.md and docs/DATA_SOURCES_AND_VERIFICATION.md).
 * Rather than simulate live data, this provider returns *distance-based planning
 * estimates with the method disclosed* plus links to the official booking sites,
 * and marks flight prices as explicitly unavailable.
 *
 * Every number it produces carries DataStatus::Estimated.
 */
class EstimatedTransportProvider implements TransportProvider
{
    public function name(): string
    {
        return 'estimate';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function plan(TripRequest $request): ProviderResult
    {
        if (! $request->hasCoordinates()) {
            return ProviderResult::unavailable(
                'Distance-based planning needs verified coordinates for both the origin city and the destination centre. We do not guess coordinates.',
            );
        }

        $straight = CityDirectory::haversineKm(
            $request->originLat, $request->originLng, $request->destinationLat, $request->destinationLng
        );
        $roadKm = CityDirectory::roadKmApprox($straight);
        $options = [];

        if ($request->wants('train')) {
            $options[] = new TravelOption(
                mode: 'train',
                label: 'Train',
                distanceKm: $roadKm,
                durationHours: round(max(0.5, $roadKm / 55), 1),
                priceMin: max(120, (int) round($roadKm * 0.45)),
                priceMax: max(200, (int) round($roadKm * 1.9)),
                priceStatus: DataStatus::Estimated->value,
                estimateMethod: 'Planning range = approximate road distance × ₹0.45–₹1.90 per km (Sleeper → AC class). Class, quota and availability change the real fare.',
                providerName: 'IRCTC (official booking)',
                sourceUrl: 'https://www.irctc.co.in',
                bookingUrl: 'https://www.irctc.co.in/nget/train-search',
                note: 'Live trains, timings and seat availability must be checked on IRCTC / NTES.',
                isEstimated: true,
            );
        }

        if ($request->wants('bus')) {
            $options[] = new TravelOption(
                mode: 'bus',
                label: 'Bus',
                distanceKm: $roadKm,
                durationHours: round(max(0.5, $roadKm / 45), 1),
                priceMin: max(100, (int) round($roadKm * 0.8)),
                priceMax: max(150, (int) round($roadKm * 2.2)),
                priceStatus: DataStatus::Estimated->value,
                estimateMethod: 'Planning range = approximate road distance × ₹0.80–₹2.20 per km (ordinary → AC). Operator and state taxes change the real fare.',
                providerName: 'State Road Transport Corporations',
                sourceUrl: 'https://parivahan.gov.in',
                bookingUrl: 'https://parivahan.gov.in',
                note: 'Use your State RTC portal (UPSRTC, MSRTC, KSRTC, RSRTC, TNSTC…) for live schedules and seats.',
                isEstimated: true,
            );
        }

        if ($request->wants('flight')) {
            $airKm = (int) round($straight);
            $options[] = new TravelOption(
                mode: 'flight',
                label: 'Flight',
                distanceKm: $airKm,
                durationHours: $straight > 250 ? round(max(1, $straight / 700) + 2, 1) : null,
                priceMin: null,
                priceMax: null,
                priceStatus: DataStatus::Unavailable->value,
                estimateMethod: 'Air fares vary by dynamic pricing, so no estimate is shown. Check airline websites for live fares.',
                providerName: 'Air India / IndiGo (official)',
                sourceUrl: 'https://www.airindia.com',
                bookingUrl: 'https://www.airindia.com',
                note: $straight <= 250
                    ? 'Short distance — flights are usually not practical; train or bus is normally better.'
                    : 'Check airport-to-centre connectivity for both cities before choosing a flight.',
                isEstimated: false,
            );
        }

        if ($request->wants('car')) {
            $options[] = new TravelOption(
                mode: 'car',
                label: 'Personal vehicle / hired cab',
                distanceKm: $roadKm,
                durationHours: round(max(0.5, $roadKm / 50), 1),
                priceMin: max(100, (int) round($roadKm * 5)),
                priceMax: max(200, (int) round($roadKm * 12)),
                priceStatus: DataStatus::Estimated->value,
                estimateMethod: 'Running-cost estimate ≈ ₹5/km (own vehicle, fuel only) to ₹12/km (hired cab with driver). Excludes tolls, parking and overnight charges.',
                providerName: 'Google Maps (route preview)',
                sourceUrl: 'https://maps.google.com',
                bookingUrl: 'https://www.google.com/maps/dir/?api=1&origin='.urlencode($request->originCity).'&destination='.urlencode($request->destinationCity),
                note: 'Verify route, tolls, parking and rest stops near the exam centre before departure.',
                isEstimated: true,
            );
        }

        if ($options === []) {
            return ProviderResult::unavailable('No travel mode was selected, so there is nothing to compare.');
        }

        return ProviderResult::ok(
            data: $options,
            sourceName: 'Distance-based estimate (method disclosed per option)',
            sourceUrl: 'https://www.irctc.co.in',
            isEstimated: true,
            meta: [
                'distance_straight_km' => (int) round($straight),
                'distance_road_km' => $roadKm,
                'road_factor' => 1.3,
                'departure_at_ist' => $request->departureInIst(),
            ],
        );
    }
}
