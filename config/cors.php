<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS) Configuration
|--------------------------------------------------------------------------
|
| Slice 6 (docs/api/v1.md §CORS) — prepared for future browser-based
| clients (e.g. a future Mobile web wrapper), NOT wide open. `allowed_origins`
| is env-driven and empty by default: with Sanctum token auth (not cookies)
| for `/api/v1/*`, CORS mostly matters for browser JS clients, and origins
| must be explicitly allow-listed per environment — never `*` combined
| with `supports_credentials: true`, which browsers reject outright and
| which would be unsafe even if they didn't.
|
*/

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Comma-separated in FINISHER_CORS_ALLOWED_ORIGINS, e.g.
    // "https://app.finisherlegacy.com,https://staging.finisherlegacy.com".
    // Empty by default — no cross-origin browser client exists yet.
    'allowed_origins' => array_filter(explode(',', (string) env('FINISHER_CORS_ALLOWED_ORIGINS', ''))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['X-Request-ID'],

    'max_age' => 0,

    // Sanctum token auth doesn't need cookies for `/api/v1/*` — keep this
    // false unless a future first-party SPA needs the `sanctum/csrf-cookie`
    // + session-cookie flow, and only ever alongside a real allow-list
    // above, never `*`.
    'supports_credentials' => false,

];
