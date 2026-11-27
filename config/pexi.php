<?php

return [
    'enabled' => env('PEXI_ENABLED', true),

    'api_key' => env('PEXI_API_KEY'),

    'endpoint' => env('PEXI_ENDPOINT'),

    'timeout' => (float) env('PEXI_TIMEOUT', 2.0),

    'retry_times' => (int) env('PEXI_RETRY_TIMES', 1),

    'retry_sleep_milliseconds' => (int) env('PEXI_RETRY_SLEEP_MILLISECONDS', 100),

    'report_in_tests' => env('PEXI_REPORT_IN_TESTS', false),

    // Leer lassen: dann gilt config('app.version') bzw. die Datei VERSION im Projekt.
    'release' => env('PEXI_RELEASE'),
];
