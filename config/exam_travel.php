<?php

use App\Integrations\Support\DataStatus;

/**
 * Product-level configuration: controlled vocabularies, retention windows and
 * freshness thresholds. Centralised so that UI labels, validation rules and
 * scheduled maintenance commands can never disagree with each other.
 */
return [

    'brand' => [
        'name' => env('APP_NAME', 'Exam Travel Assist'),
        'support_email' => env('SUPPORT_EMAIL', 'support@example.com'),
        'tagline' => 'Plan your trip to the exam centre with information you can actually check.',
        // Displayed in the footer. The product must never imply official affiliation.
        'disclaimer' => 'An independent student help platform. Not affiliated with NTA, SSC, UPSC, any examination authority, Indian Railways, IRCTC, or any transport operator or government body.',
    ],

    'service_categories' => [
        'hotel' => 'Hotel',
        'lodge' => 'Lodge / Dharamshala',
        'waiting_lounge' => 'Waiting lounge',
        'restaurant' => 'Restaurant',
        'breakfast' => 'Breakfast spot',
        'stationery' => 'Stationery / photocopy shop',
        'other' => 'Other',
    ],

    'travel_modes' => [
        'train' => 'Train',
        'bus' => 'Bus',
        'flight' => 'Flight',
        'car' => 'Personal vehicle / cab',
        'walk' => 'Walking',
        'auto' => 'Auto-rickshaw',
        'cab' => 'Cab / bike taxi',
    ],

    'community_categories' => [
        'general' => 'General',
        'travel' => 'Travel & transport',
        'stay' => 'Stay & food',
        'exam_day' => 'Exam day',
        'safety' => 'Safety',
    ],

    'data_statuses' => DataStatus::values(),

    /*
    |--------------------------------------------------------------------------
    | Freshness thresholds
    |--------------------------------------------------------------------------
    | Verified information older than this is displayed as "stale" and flagged for
    | re-verification in the admin panel. Missing verification is never treated as
    | fresh.
    */
    'freshness' => [
        'centre_months' => (int) env('CENTRE_FRESHNESS_MONTHS', 6),
        'service_months' => (int) env('SERVICE_FRESHNESS_MONTHS', 6),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    | Used by the scheduled maintenance commands. Documented in
    | docs/SECURITY_AND_PRIVACY.md and docs/DATABASE_SCHEMA.md.
    */
    'retention' => [
        'chat_days' => (int) env('CHAT_RETENTION_DAYS', 90),
        'search_log_days' => (int) env('SEARCH_LOG_RETENTION_DAYS', 180),
        'integration_event_days' => (int) env('INTEGRATION_EVENT_RETENTION_DAYS', 90),
        'deletion_grace_days' => (int) env('ACCOUNT_DELETION_GRACE_DAYS', 30),
        'import_row_days' => (int) env('IMPORT_ROW_RETENTION_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Assistant limits
    |--------------------------------------------------------------------------
    */
    'security' => [
        // Off by default so a student can search and plan immediately. Turn on in
        // production if you require confirmed addresses (needs working MAIL_* config).
        'require_email_verification' => (bool) env('REQUIRE_EMAIL_VERIFICATION', false),
    ],
    'assistant' => [
        'max_question_length' => 1000,
        'max_messages_per_conversation' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Place search defaults
    |--------------------------------------------------------------------------
    */
    'places' => [
        'radius_options' => [1000, 3000, 5000, 10000],
        'default_radius' => 3000,
    ],
];
