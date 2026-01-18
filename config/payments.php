<?php

return [
    // we'll use here local disk for local/dev and S3 for staging/prod
    'upload_disk' => env('PAYMENTS_UPLOAD_DISK', env('APP_ENV') ? 'local' : 's3'),

    // place where the chunk files and logs are written (defaults to upload_disk)
    'work_disk' => env('PAYMENTS_WORK_DISK', env('PAYMENTS_UPLOAD_DISK', env('APP_ENV') ? 'local' : 's3')),

    // Rows per chunk file
    'chunk_rows' => (int) env('PAYMENTS_CHUNK_ROWS', 10000),

    // Queue names
    'queue' => [
        'chunking' => env('PAYMENTS_QUEUE_CHUNKING', 'imports'),
        'processing' => env('PAYMENTS_QUEUE_PROCESSING', 'imports'),
    ],

    // Exchange rates provider selection
    'exchange' => [
        'provider' => env('EXCHANGE_PROVIDER', 'frankfurter'),
        'base' => env('EXCHANGE_BASE', 'USD'),
        'cache_ttl_seconds' => (int) env('EXCHANGE_CACHE_TTL', 3600),
    ],
];