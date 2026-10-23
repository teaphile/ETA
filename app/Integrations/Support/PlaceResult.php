<?php

namespace App\Integrations\Support;

use App\Models\LocalService;

/**
 * A single local service (hotel, lodge, restaurant, stationery shop, ...).
 *
 * `rating` is always a *provider rating* — never a safety assessment. The UI must
 * label it as such. A missing price is stored as null and rendered as "price
 * unavailable"; it is never treated as zero.
 */
final readonly class PlaceResult
{
    public function __construct(
        public string $name,
        public string $category,
        public ?string $address = null,
        public ?string $city = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?float $distanceKm = null,
        public ?string $phone = null,
        public ?string $website = null,
        public ?string $openingHours = null,
        public ?float $rating = null,
        public ?int $reviewsCount = null,
        public ?int $priceFrom = null,
        public string $sourceName = 'Curated listing',
        public ?string $sourceUrl = null,
        public ?string $providerReference = null,
        public string $dataStatus = DataStatus::Unverified->value,
        public ?string $retrievedAt = null,
        public ?string $lastVerifiedAt = null,
        public bool $isDemo = false,
        public ?string $navigationUrl = null,
        public ?string $errorOrLimitation = null,
        // Present only for curated listings stored in `local_services`, so the UI can
        // offer save/unsave. Third-party (provider) results are never savable.
        public ?int $localServiceId = null,
    ) {}

    public function hasPrice(): bool
    {
        return $this->priceFrom !== null;
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function mapsUrl(): ?string
    {
        if ($this->navigationUrl) {
            return $this->navigationUrl;
        }

        return $this->hasCoordinates()
            ? 'https://www.google.com/maps/dir/?api=1&destination='.$this->latitude.','.$this->longitude
            : null;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /** Convert an Eloquent LocalService model into a normalised place result. */
    public static function fromModel(LocalService $m, ?float $distanceKm = null): self
    {
        return new self(
            name: (string) $m->name,
            category: (string) $m->category,
            address: $m->address,
            city: $m->city,
            latitude: $m->latitude !== null ? (float) $m->latitude : null,
            longitude: $m->longitude !== null ? (float) $m->longitude : null,
            distanceKm: $distanceKm,
            phone: $m->phone,
            website: $m->website,
            openingHours: $m->opening_hours,
            rating: $m->rating !== null ? (float) $m->rating : null,
            reviewsCount: $m->reviews_count !== null ? (int) $m->reviews_count : null,
            priceFrom: $m->price_from !== null ? (int) $m->price_from : null,
            sourceName: (string) ($m->source_name ?: 'Curated listing'),
            sourceUrl: $m->source_url,
            providerReference: $m->provider_reference,
            dataStatus: (string) ($m->data_status ?: DataStatus::Unverified->value),
            retrievedAt: $m->retrieved_at?->toIso8601String(),
            lastVerifiedAt: $m->last_verified_at?->toIso8601String(),
            isDemo: (bool) $m->is_demo,
            errorOrLimitation: $m->error_or_limitation,
            localServiceId: (int) $m->id,
        );
    }
}
