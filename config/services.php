<?php

/**
 * Third-party service configuration.
 *
 * IMPORTANT: This file is the single source of truth for integration credentials.
 * It intentionally replaces a previously duplicated `config/Services.php` file, which
 * collided with this file on case-insensitive filesystems (macOS/Windows) and caused
 * integrations to be silently misconfigured depending on the operating system.
 *
 * Never commit real credentials. Every value below is read from the environment and
 * falls back to a safe (usually "disabled") default so the app runs with zero keys.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Mapping / GIS (Google Maps Platform)
    |--------------------------------------------------------------------------
    | browser_key : restricted, referrer-limited key for Maps JS in the browser.
    | server_key  : IP/API-restricted key used for server-side Geocoding/Directions.
    | When empty, the app falls back to keyless Google Maps deep links and clearly
    | labelled text directions. It never invents a route.
    */
    'google_maps' => [
        'browser_key' => env('GOOGLE_MAPS_BROWSER_KEY', ''),
        'server_key' => env('GOOGLE_MAPS_SERVER_KEY', ''),
        'region' => env('GOOGLE_MAPS_REGION', 'IN'),
        'language' => env('GOOGLE_MAPS_LANGUAGE', 'en'),
        'timeout' => (int) env('GOOGLE_MAPS_TIMEOUT', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Places discovery
    |--------------------------------------------------------------------------
    | provider: curated | google | null
    |   curated -> administrator-maintained listings stored in `local_services`
    |   google  -> Google Places API (Nearby Search / Place Details)
    |   null    -> explicitly unavailable; UI shows an honest empty state
    */
    'places' => [
        'provider' => env('PLACES_PROVIDER', 'curated'),
        'server_key' => env('GOOGLE_PLACES_SERVER_KEY', env('GOOGLE_MAPS_SERVER_KEY', '')),
        'timeout' => (int) env('PLACES_TIMEOUT', 8),
        'radius_default_m' => (int) env('PLACES_RADIUS_DEFAULT_M', 3000),
        'max_results' => (int) env('PLACES_MAX_RESULTS', 10),
        // Google Places terms require results to be cached no longer than this.
        'cache_seconds' => (int) env('PLACES_CACHE_SECONDS', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI chatbot
    |--------------------------------------------------------------------------
    | provider: faq (grounded DB + FAQ only, no external call) | openai
    | The service is provider-independent: see App\Contracts\AIProvider.
    */
    'ai' => [
        'provider' => env('AI_PROVIDER', 'faq'),
        'openai' => [
            'key' => env('OPENAI_API_KEY', ''),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'timeout' => (int) env('AI_TIMEOUT', 20),
            'max_tokens' => (int) env('AI_MAX_TOKENS', 600),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Transport data
    |--------------------------------------------------------------------------
    | India has no public, openly licensed live rail/bus/flight fare API. The
    | default provider therefore returns *clearly labelled distance-based
    | estimates* plus official booking links, never simulated fares.
    */
    'transport' => [
        'provider' => env('TRANSPORT_PROVIDER', 'estimate'),
        'key' => env('TRANSPORT_API_KEY', ''),
        'base_url' => env('TRANSPORT_API_BASE_URL', ''),
        'timeout' => (int) env('TRANSPORT_TIMEOUT', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Transactional email / notifications
    |--------------------------------------------------------------------------
    | 'log' writes notifications to the Laravel log (safe local default).
    */
    'notifications' => [
        'provider' => env('NOTIFICATION_PROVIDER', 'mail'),
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
