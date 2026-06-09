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

final class CustomLibraryItemStub extends LibraryItem {}
