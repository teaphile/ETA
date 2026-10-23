<?php

namespace App\Integrations\Support;

/**
 * One normalised travel option produced by any TransportProvider adapter.
 *
 * Only fields the provider actually supplies are ever populated. A null price with
 * `priceStatus = unavailable` must be rendered as "Price unavailable" — never as ₹0.
 */
final readonly class TravelOption
{
    public function __construct(
        public string $mode,
        public string $label,
        public ?int $distanceKm = null,
        public ?float $durationHours = null,
        public ?int $priceMin = null,
        public ?int $priceMax = null,
        public string $currency = 'INR',
        public string $priceStatus = DataStatus::Unavailable->value,
        public ?string $estimateMethod = null,
        public ?string $providerName = null,
        public ?string $sourceUrl = null,
        public ?string $bookingUrl = null,
        public ?string $retrievedAt = null,
        public ?string $note = null,
        public bool $isEstimated = false,
    ) {}

    public function hasPrice(): bool
    {
        return $this->priceMin !== null;
    }

    /** True when the option's price is a disclosed estimate rather than an observed fare. */
    public function priceIsEstimated(): bool
    {
        return $this->hasPrice() && $this->isEstimated;
    }

    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'label' => $this->label,
            'distance_km' => $this->distanceKm,
            'duration_hours' => $this->durationHours,
            'price_min' => $this->priceMin,
            'price_max' => $this->priceMax,
            'currency' => $this->currency,
            'price_status' => $this->priceStatus,
            'estimate_method' => $this->estimateMethod,
            'provider_name' => $this->providerName,
            'source_url' => $this->sourceUrl,
            'booking_url' => $this->bookingUrl,
            'retrieved_at' => $this->retrievedAt,
            'note' => $this->note,
            'is_estimated' => $this->isEstimated,
        ];
    }

    /**
     * Rehydrate from a stored itinerary snapshot.
     *
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            mode: (string) ($data['mode'] ?? 'unknown'),
            label: (string) ($data['label'] ?? 'Travel option'),
            distanceKm: isset($data['distance_km']) ? (int) $data['distance_km'] : null,
            durationHours: isset($data['duration_hours']) ? (float) $data['duration_hours'] : null,
            priceMin: isset($data['price_min']) ? (int) $data['price_min'] : null,
            priceMax: isset($data['price_max']) ? (int) $data['price_max'] : null,
            currency: (string) ($data['currency'] ?? 'INR'),
            priceStatus: (string) ($data['price_status'] ?? DataStatus::Unavailable->value),
            estimateMethod: $data['estimate_method'] ?? null,
            providerName: $data['provider_name'] ?? null,
            sourceUrl: $data['source_url'] ?? null,
            bookingUrl: $data['booking_url'] ?? null,
            retrievedAt: $data['retrieved_at'] ?? null,
            note: $data['note'] ?? null,
            isEstimated: (bool) ($data['is_estimated'] ?? false),
        );
    }
}
