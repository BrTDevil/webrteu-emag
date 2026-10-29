<?php

declare(strict_types=1);

return [
    // eMAG Marketplace API v3: .ro / .bg / .hu (sau fashiondays), cu IP-ul serverului în whitelist.
    'base_url' => env('EMAG_BASE_URL', 'https://marketplace-api.emag.ro/api-3'),
    'username' => env('EMAG_USERNAME', ''),
    'password' => env('EMAG_PASSWORD', ''),
    'timeout' => (int) env('EMAG_TIMEOUT', 30),
    'items_per_page' => (int) env('EMAG_ITEMS_PER_PAGE', 100),

    // Limita eMAG e de 3 cereri/secundă pentru resursele non-comenzi.
    'throttle_ms' => (int) env('EMAG_THROTTLE_MS', 350),

    'images' => [
        'download' => (bool) env('EMAG_DOWNLOAD_IMAGES', true),
        'disk' => env('EMAG_IMAGES_DISK', 'public'),
        'path' => env('EMAG_IMAGES_PATH', 'emag'),
        'timeout' => (int) env('EMAG_IMAGES_TIMEOUT', 30),
    ],
];
