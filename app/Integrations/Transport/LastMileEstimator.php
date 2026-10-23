<?php

namespace App\Integrations\Transport;

use App\Integrations\Support\DataStatus;
use App\Integrations\Support\TravelOption;

/**
 * Last-mile options from an arrival point (station / airport / city reference point)
 * to the examination centre.
 *
 * Fare figures are disclosed planning estimates in Indian rupees. Live availability
 * and surge pricing for ride-hailing apps (Rapido, Ola, Uber) are only visible inside
 * those apps, so those rows are marked `unavailable_live` and never presented as a
 * confirmed fare or a confirmed booking.
 */
class LastMileEstimator
{
    /** @return array<int,TravelOption> */
    public function options(float $km, ?string $mapsBookingUrl = null): array
    {
        $km = max(0.3, $km);

        return [
            new TravelOption(
                mode: 'walk',
                label: 'Walk',
                distanceKm: (int) round($km),
                durationHours: round($km / 4.5, 2),
                priceMin: 0,
                priceMax: 0,
                priceStatus: DataStatus::Estimated->value,
                estimateMethod: 'Walking time uses 4.5 km/h on flat ground; actual pavements, crossings and heat will differ.',
                providerName: 'No provider required',
                note: $km > 3
                    ? 'Long walk for exam day — consider an auto or city bus, especially with luggage.'
                    : 'Reasonable if the route is safe and you have time. Rehearse it once.',
                isEstimated: true,
            ),
            new TravelOption(
                mode: 'auto',
                label: 'Auto-rickshaw',
                distanceKm: (int) round($km),
                durationHours: round($km / 22, 2),
                priceMin: max(30, (int) round($km * 18)),
                priceMax: max(40, (int) round($km * 25)),
                priceStatus: DataStatus::Estimated->value,
                estimateMethod: 'Planning range ≈ ₹18–₹25 per km. Prepaid booths and metered autos are cheaper than negotiated fares.',
                providerName: 'Local auto union / prepaid booth',
                bookingUrl: $mapsBookingUrl,
                note: 'Agree the fare (or insist on the meter) before starting. Keep small change ready.',
                isEstimated: true,
            ),
            new TravelOption(
                mode: 'cab',
                label: 'Cab / bike taxi (Rapido, Ola, Uber…)',
                distanceKm: (int) round($km),
                durationHours: round($km / 25, 2),
                priceMin: null,
                priceMax: null,
                priceStatus: 'unavailable_live',
                estimateMethod: 'No estimate is shown: ride-hailing fares are dynamic and availability is live-only. Open the app to see the actual quote.',
                providerName: 'Rapido / Ola / Uber (in-app only)',
                bookingUrl: null,
                note: 'Book from inside the app. Surge pricing, waiting charges and cancellations are common on exam mornings — allow extra time.',
                isEstimated: false,
            ),
            new TravelOption(
                mode: 'bus',
                label: 'City bus / Metro',
                distanceKm: (int) round($km),
                durationHours: round($km / 18, 2),
                priceMin: max(10, (int) round($km * 2.5)),
                priceMax: max(15, (int) round($km * 4)),
                priceStatus: DataStatus::Estimated->value,
                estimateMethod: 'Planning range ≈ ₹2.50–₹4 per km for ordinary to AC city services.',
                providerName: 'State/City transport corporation',
                note: 'Cheapest option. Verify route numbers locally or on a transit app; metro stations may be far from the centre.',
                isEstimated: true,
            ),
        ];
    }
}
