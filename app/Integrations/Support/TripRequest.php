<?php

namespace App\Integrations\Support;

use Carbon\CarbonImmutable;

/**
 * A normalised journey request. Timezone handling is explicit: all India-facing
 * scheduling uses Asia/Kolkata (IST), never the server's local timezone.
 */
final readonly class TripRequest
{
    public const TIMEZONE = 'Asia/Kolkata';

    /**
     * @param  array<int,string>  $modes
     */
    public function __construct(
        public string $originCity,
        public string $destinationCity,
        public ?float $originLat = null,
        public ?float $originLng = null,
        public ?float $destinationLat = null,
        public ?float $destinationLng = null,
        public ?CarbonImmutable $departureAt = null,
        public ?int $budget = null,
        public array $modes = ['train', 'bus', 'flight', 'car'],
        public ?int $examCentreId = null,
    ) {}

    public function hasCoordinates(): bool
    {
        return $this->originLat !== null && $this->originLng !== null
            && $this->destinationLat !== null && $this->destinationLng !== null;
    }

    public function wants(string $mode): bool
    {
        return in_array($mode, $this->modes, true);
    }

    public function departureInIst(): ?string
    {
        return $this->departureAt?->setTimezone(self::TIMEZONE)->toIso8601String();
    }

    public function toArray(): array
    {
        return [
            'origin_city' => $this->originCity,
            'destination_city' => $this->destinationCity,
            'origin_lat' => $this->originLat,
            'origin_lng' => $this->originLng,
            'destination_lat' => $this->destinationLat,
            'destination_lng' => $this->destinationLng,
            'departure_at' => $this->departureInIst(),
            'budget' => $this->budget,
            'modes' => $this->modes,
            'exam_centre_id' => $this->examCentreId,
        ];
    }
}
