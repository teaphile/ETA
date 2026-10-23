<?php

namespace Tests\Unit;

use App\Integrations\Support\CityDirectory;
use App\Integrations\Support\DataStatus;
use App\Integrations\Support\JourneyPlan;
use App\Integrations\Support\TravelOption;
use Tests\TestCase;

class SupportTest extends TestCase
{
    public function test_data_status_vocabulary_and_labels(): void
    {
        $this->assertContains('officially_verified', DataStatus::values());
        $this->assertContains('unavailable', DataStatus::values());
        $this->assertSame('Officially verified', DataStatus::label('officially_verified'));
        $this->assertSame('Unknown', DataStatus::label('nonsense'));
        $this->assertTrue(DataStatus::isVerified('admin_verified'));
        $this->assertFalse(DataStatus::isVerified('unverified'));
        $this->assertTrue(DataStatus::isTrustworthy('third_party'));
        $this->assertFalse(DataStatus::isTrustworthy('unverified'));
    }

    public function test_city_directory_resolution_is_conservative(): void
    {
        $this->assertSame([28.6139, 77.2090], CityDirectory::coords('Delhi'));
        $this->assertNotNull(CityDirectory::coords('  new   delhi '));
        $this->assertNotNull(CityDirectory::coords('Delhi, India'));
        // A city we do not hold coordinates for is never guessed.
        $this->assertNull(CityDirectory::coords('Definitely Not A City'));
        $this->assertNull(CityDirectory::coords(''));
    }

    public function test_road_distance_uses_disclosed_detour_factor(): void
    {
        $this->assertSame(130, CityDirectory::roadKmApprox(100.0));
        $this->assertSame(1, CityDirectory::roadKmApprox(0.0));
    }

    public function test_haversine_is_symmetric_and_zero_for_same_point(): void
    {
        $a = CityDirectory::haversineKm(28.6139, 77.2090, 19.0760, 72.8777);
        $b = CityDirectory::haversineKm(19.0760, 72.8777, 28.6139, 77.2090);
        $this->assertEqualsWithDelta($a, $b, 0.0001);
        $this->assertSame(0.0, CityDirectory::haversineKm(28.0, 77.0, 28.0, 77.0));
    }

    public function test_travel_option_round_trips_without_inventing_a_price(): void
    {
        $option = new TravelOption(
            mode: 'flight',
            label: 'Flight',
            priceMin: null,
            priceMax: null,
            priceStatus: DataStatus::Unavailable->value,
        );

        $this->assertFalse($option->hasPrice());

        $rehydrated = TravelOption::fromArray($option->toArray());
        $this->assertNull($rehydrated->priceMin);
        $this->assertSame(DataStatus::Unavailable->value, $rehydrated->priceStatus);
        $this->assertFalse($rehydrated->hasPrice());
    }

    public function test_journey_plan_lowest_price_ignores_unpriced_options(): void
    {
        $plan = new JourneyPlan(intercityOptions: [
            new TravelOption(mode: 'train', label: 'Train', priceMin: 500, priceMax: 900),
            new TravelOption(mode: 'flight', label: 'Flight'), // no price
            new TravelOption(mode: 'bus', label: 'Bus', priceMin: 200, priceMax: 400),
        ]);

        $this->assertTrue($plan->hasOptions());
        $this->assertSame('Bus', $plan->lowestPricedAmongResults()?->label);
    }

    public function test_journey_plan_unavailable_is_explicit(): void
    {
        $plan = JourneyPlan::unavailable('No coordinates available.');

        $this->assertFalse($plan->hasOptions());
        $this->assertSame(DataStatus::Unavailable->value, $plan->intercityStatus);
        $this->assertSame('No coordinates available.', $plan->unavailableReason);
    }

    public function test_journey_plan_and_travel_option_round_trip(): void
    {
        $plan = new JourneyPlan(
            intercityOptions: [new TravelOption(mode: 'train', label: 'Train', priceMin: 120, priceMax: 200)],
            intercityStatus: DataStatus::Estimated->value,
            isEstimated: true,
            straightKm: 900,
            roadKm: 1170,
        );

        $restored = JourneyPlan::fromArray($plan->toArray());

        $this->assertTrue($restored->hasOptions());
        $this->assertTrue($restored->isEstimated);
        $this->assertSame(1170, $restored->roadKm);
        $this->assertSame('Train', $restored->intercityOptions[0]->label);
    }
}
