<?php

namespace App\Integrations\Support;

/**
 * The complete, honest result of a journey-planning request.
 *
 * It deliberately separates "we could not compute this" from "we computed a disclosed
 * estimate", and it records every limitation so the UI can show why something is
 * missing rather than quietly dropping it.
 */
final readonly class JourneyPlan
{
    /**
     * @param  array<int,TravelOption>  $intercityOptions
     * @param  array<int,TravelOption>  $lastMileOptions
     * @param  array<string,string>  $resolutionNotes  origin/destination resolution explanations
     * @param  array<string,string>  $limitations
     */
    public function __construct(
        public array $intercityOptions = [],
        public array $lastMileOptions = [],
        public string $intercityStatus = DataStatus::Unavailable->value,
        public ?string $providerName = null,
        public ?string $providerSourceUrl = null,
        public bool $isEstimated = false,
        public ?int $straightKm = null,
        public ?int $roadKm = null,
        public ?float $lastMileKm = null,
        public array $resolutionNotes = [],
        public array $limitations = [],
        public ?string $unavailableReason = null,
        public ?string $retrievedAt = null,
    ) {}

    public static function unavailable(string $reason, array $resolutionNotes = []): self
    {
        return new self(
            intercityStatus: DataStatus::Unavailable->value,
            resolutionNotes: $resolutionNotes,
            unavailableReason: $reason,
            retrievedAt: now()->toIso8601String(),
        );
    }

    public function hasOptions(): bool
    {
        return $this->intercityOptions !== [];
    }

    /**
     * Lowest *priced* option among the results actually retrieved.
     *
     * The label matters: we never claim to have found the cheapest option in the
     * market, only the lowest priced among what we could retrieve.
     */
    public function lowestPricedAmongResults(): ?TravelOption
    {
        $priced = array_filter($this->intercityOptions, fn (TravelOption $o) => $o->hasPrice());

        if ($priced === []) {
            return null;
        }

        usort($priced, fn (TravelOption $a, TravelOption $b) => ($a->priceMin ?? 0) <=> ($b->priceMin ?? 0));

        return $priced[0];
    }

    /** @return array<int,string> */
    public function priceSummaries(): array
    {
        return array_map(fn (TravelOption $o) => $o->hasPrice()
            ? sprintf('%s: ₹%s–₹%s (%s)', $o->label, number_format($o->priceMin), number_format((int) $o->priceMax), $o->priceStatus)
            : sprintf('%s: price unavailable', $o->label), $this->intercityOptions);
    }

    public function toArray(): array
    {
        return [
            'intercity_options' => array_map(fn (TravelOption $o) => $o->toArray(), $this->intercityOptions),
            'last_mile_options' => array_map(fn (TravelOption $o) => $o->toArray(), $this->lastMileOptions),
            'intercity_status' => $this->intercityStatus,
            'provider_name' => $this->providerName,
            'provider_source_url' => $this->providerSourceUrl,
            'is_estimated' => $this->isEstimated,
            'distance_straight_km' => $this->straightKm,
            'distance_road_km' => $this->roadKm,
            'last_mile_km' => $this->lastMileKm,
            'resolution_notes' => $this->resolutionNotes,
            'limitations' => $this->limitations,
            'unavailable_reason' => $this->unavailableReason,
            'retrieved_at' => $this->retrievedAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            intercityOptions: array_map(fn ($o) => TravelOption::fromArray($o), $data['intercity_options'] ?? $data['options'] ?? []),
            lastMileOptions: array_map(fn ($o) => TravelOption::fromArray($o), $data['last_mile_options'] ?? []),
            intercityStatus: $data['intercity_status'] ?? $data['status'] ?? DataStatus::Unavailable->value,
            providerName: $data['provider_name'] ?? null,
            providerSourceUrl: $data['provider_source_url'] ?? $data['source_url'] ?? null,
            isEstimated: (bool) ($data['is_estimated'] ?? false),
            straightKm: $data['distance_straight_km'] ?? null,
            roadKm: $data['distance_road_km'] ?? null,
            lastMileKm: $data['last_mile_km'] ?? null,
            resolutionNotes: $data['resolution_notes'] ?? [],
            limitations: $data['limitations'] ?? [],
            unavailableReason: $data['unavailable_reason'] ?? null,
            retrievedAt: $data['retrieved_at'] ?? null,
        );
    }
}
