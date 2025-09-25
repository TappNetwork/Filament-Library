<?php

return [
    /*
    |--------------------------------------------------------------------------
    | URL Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for URL generation and expiration times.
    |
    */
    'url' => [
        'temporary_expiration_minutes' => env('FILAMENT_LIBRARY_URL_EXPIRATION_MINUTES', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for caching behavior.
    |
    */
    'cache' => [
        'breadcrumbs_ttl_seconds' => env('FILAMENT_LIBRARY_BREADCRUMBS_TTL', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for video URL detection.
    |
    */
    'video' => [
        'supported_domains' => [
            'youtube.com',
            'youtu.be',
            'vimeo.com',
            'wistia.com',
        ],
    ],
];
