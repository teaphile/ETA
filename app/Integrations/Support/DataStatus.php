<?php

namespace App\Integrations\Support;

/**
 * Controlled vocabulary for the reliability of a piece of information.
 *
 * This is the single source of truth used by every module so that the UI, the
 * chatbot and the admin screens all describe data in the same words.
 */
enum DataStatus: string
{
    /** Backed by an identifiable official source with recorded evidence. */
    case OfficiallyVerified = 'officially_verified';

    /** Reviewed and approved by an authorised administrator. */
    case AdminVerified = 'admin_verified';

    /** Retrieved from a recognised provider but not independently confirmed official. */
    case ThirdParty = 'third_party';

    /** Computed or approximated using a disclosed method and assumptions. */
    case Estimated = 'estimated';

    /** Stored but not yet checked against any source. */
    case Unverified = 'unverified';

    /** Previously available information that is now past its validity window. */
    case Stale = 'stale';

    /** No reliable result could be obtained. */
    case Unavailable = 'unavailable';

    /** @return array<int,string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }

    public static function isVerified(?string $status): bool
    {
        return in_array($status, [self::OfficiallyVerified->value, self::AdminVerified->value], true);
    }

    public static function label(?string $status): string
    {
        return match ($status) {
            self::OfficiallyVerified->value => 'Officially verified',
            self::AdminVerified->value => 'Admin verified',
            self::ThirdParty->value => 'Third-party source',
            self::Estimated->value => 'Estimated',
            self::Unverified->value => 'Unverified',
            self::Stale->value => 'Stale — needs re-checking',
            self::Unavailable->value => 'Unavailable',
            default => 'Unknown',
        };
    }

    /** Whether the value may be shown as trustworthy without a caveat. */
    public static function isTrustworthy(?string $status): bool
    {
        return in_array($status, [self::OfficiallyVerified->value, self::AdminVerified->value, self::ThirdParty->value], true);
    }
}
