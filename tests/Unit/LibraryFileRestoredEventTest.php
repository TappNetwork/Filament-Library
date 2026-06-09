<?php

use Illuminate\Support\Facades\Event;
use Tapp\FilamentLibrary\Events\LibraryFileRestored;
use Tapp\FilamentLibrary\Models\LibraryItem;

test('LibraryFileRestored is dispatched when a library file is restored', function (): void {
    Event::fake([LibraryFileRestored::class]);

    $item = LibraryItem::query()->create([
        'name' => 'Restored brief.pdf',
        'slug' => 'restored-brief-' . uniqid('', true),
        'type' => 'file',
        'created_by' => 1,
        'updated_by' => 1,
        'general_access' => 'private',
    ]);

    $item->delete();
    $item->restore();

    Event::assertDispatched(LibraryFileRestored::class, function (LibraryFileRestored $event) use ($item): bool {
        return $event->libraryItem->is($item);
    });
});

test('LibraryFileRestored is not dispatched when a folder is restored', function (): void {
    Event::fake([LibraryFileRestored::class]);

    $item = LibraryItem::query()->create([
        'name' => 'Restored folder',
        'slug' => 'restored-folder-' . uniqid('', true),
        'type' => 'folder',
        'created_by' => 1,
        'updated_by' => 1,
        'general_access' => 'private',
    ]);

    $item->delete();
    $item->restore();

    Event::assertNotDispatched(LibraryFileRestored::class);
});
