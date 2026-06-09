<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Tapp\FilamentLibrary\Support\LibraryFolderBreadcrumbPath;

it('caches folder breadcrumb paths as plain arrays', function (): void {
    Cache::flush();

    $path = [
        ['id' => 4, 'name' => 'Atlas Preview Samples'],
    ];

    Cache::put('filament-library.breadcrumbs.global.4', $path, 300);

    $cached = Cache::get('filament-library.breadcrumbs.global.4');

    expect($cached)->toBe($path)
        ->and($cached[0]['name'])->toBe('Atlas Preview Samples');
});

it('does not return incomplete class objects from breadcrumb cache', function (): void {
    Cache::flush();

    Cache::put('breadcrumbs_4', [(object) ['id' => 4]], 300);

    $legacyCached = Cache::get('breadcrumbs_4');

    expect($legacyCached[0])->toBeObject();

    $path = LibraryFolderBreadcrumbPath::ancestorsForParentId(null);

    expect($path)->toBe([]);
});
