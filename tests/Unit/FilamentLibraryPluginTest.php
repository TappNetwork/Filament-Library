<?php

use Tapp\FilamentLibrary\FilamentLibraryPlugin;
use Tapp\FilamentLibrary\Models\LibraryItem;
use Tapp\FilamentLibrary\Models\LibraryItemPermission;
use Tapp\FilamentLibrary\Models\LibraryItemTag;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

test('library models resolve from config', function (): void {
    config()->set('filament-library.models', [
        'LibraryItem' => LibraryItem::class,
        'LibraryItemPermission' => LibraryItemPermission::class,
        'LibraryItemTag' => LibraryItemTag::class,
    ]);

    expect(FilamentLibraryPlugin::modelClass('LibraryItem'))->toBe(LibraryItem::class)
        ->and(FilamentLibraryPlugin::libraryItemModelClass())->toBe(LibraryItem::class)
        ->and(FilamentLibraryPlugin::libraryItemPermissionModelClass())->toBe(LibraryItemPermission::class)
        ->and(FilamentLibraryPlugin::libraryItemTagModelClass())->toBe(LibraryItemTag::class);
});

test('library item resource uses configured model class', function (): void {
    config()->set('filament-library.models.LibraryItem', CustomLibraryItemStub::class);

    expect(LibraryItemResource::getModel())->toBe(CustomLibraryItemStub::class);
});

test('navigation is visible by default', function (): void {
    $plugin = FilamentLibraryPlugin::make();

    expect($plugin->isNavigationVisible())->toBeTrue();
});

test('navigationVisibleUsing accepts a boolean', function (): void {
    $plugin = FilamentLibraryPlugin::make()
        ->navigationVisibleUsing(false);

    expect($plugin->isNavigationVisible())->toBeFalse();
});

test('navigationVisibleUsing accepts a closure', function (): void {
    $visible = false;

    $plugin = FilamentLibraryPlugin::make()
        ->navigationVisibleUsing(function () use (&$visible): bool {
            return $visible;
        });

    expect($plugin->isNavigationVisible())->toBeFalse();

    $visible = true;

    expect($plugin->isNavigationVisible())->toBeTrue();
});

final class CustomLibraryItemStub extends LibraryItem {}
