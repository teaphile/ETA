<?php

namespace App\Integrations\Support;

/**
 * Standard envelope returned by every external provider adapter.
 *
 * Business logic never couples to a vendor: it inspects `status` and reads `data`,
 * so an unavailable provider produces an honest empty state rather than an exception
 * or fabricated data.
 *
 * @template T
 */
final readonly class ProviderResult
{
    /**
     * @param  T  $data
     */
    private function __construct(
        public string $status,
        public mixed $data = null,
        public ?string $sourceName = null,
        public ?string $sourceUrl = null,
        public ?string $retrievedAt = null,
        public ?string $error = null,
        public bool $isEstimated = false,
        public array $meta = [],
    ) {}

    /**
     * @param  T  $data
     */
    public static function ok(
        mixed $data,
        ?string $sourceName = null,
        ?string $sourceUrl = null,
        bool $isEstimated = false,
        array $meta = [],
    ): self {
        return new self(
            status: $isEstimated ? DataStatus::Estimated->value : DataStatus::ThirdParty->value,
            data: $data,
            sourceName: $sourceName,
            sourceUrl: $sourceUrl,
            retrievedAt: now()->toIso8601String(),
            isEstimated: $isEstimated,
            meta: $meta,
        );
    }

    /** Verified against an explicitly identified official source. */
    public static function verified(mixed $data, ?string $sourceName = null, ?string $sourceUrl = null, array $meta = []): self
    {
        return new self(
            status: DataStatus::OfficiallyVerified->value,
            data: $data,
            sourceName: $sourceName,
            sourceUrl: $sourceUrl,
            retrievedAt: now()->toIso8601String(),
            meta: $meta,
        );
    }

    /** No reliable data — the caller must render an "unavailable" state. */
    public static function unavailable(string $reason, ?string $sourceName = null, ?string $sourceUrl = null): self
    {
        return new self(
            status: DataStatus::Unavailable->value,
            sourceName: $sourceName,
            sourceUrl: $sourceUrl,
            error: $reason,
            retrievedAt: now()->toIso8601String(),
        );
    }

    /** A provider failed (timeout, quota, malformed response). Never contains credentials. */
    public static function failed(string $safeReason, ?string $sourceName = null): self
    {
        return new self(
            status: DataStatus::Unavailable->value,
            sourceName: $sourceName,
            error: $safeReason,
            retrievedAt: now()->toIso8601String(),
        );
    }

    public function isAvailable(): bool
    {
        return $this->status !== DataStatus::Unavailable->value;
    }

    public function isEmpty(): bool
    {
        return $this->isAvailable() && (is_array($this->data) ? $this->data === [] : $this->data === null);
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'data' => $this->data,
            'source_name' => $this->sourceName,
            'source_url' => $this->sourceUrl,
            'retrieved_at' => $this->retrievedAt,
            'error' => $this->error,
            'is_estimated' => $this->isEstimated,
            'meta' => $this->meta,
        ];
    }
}
