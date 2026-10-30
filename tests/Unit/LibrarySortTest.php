<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Tapp\FilamentLibrary\Enums\LibrarySort;
use Tapp\FilamentLibrary\FilamentLibraryPlugin;
use Tapp\FilamentLibrary\Models\LibraryItem;

test('library listings default to folders first then name', function () {
    $userId = DB::table('users')->insertGetId([
        'name' => 'Librarian',
        'email' => 'librarian@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $parent = librarySortItem($userId, 'Reference', 'folder');

    librarySortItem($userId, 'Mango', 'folder', $parent->id);
    librarySortItem($userId, 'Apples', 'file', $parent->id);
    librarySortItem($userId, 'Zebra', 'folder', $parent->id);
    librarySortItem($userId, 'Bananas', 'link', $parent->id);
    librarySortItem($userId, 'Root file', 'file');

    $plugin = FilamentLibraryPlugin::make();

    expect($plugin->getDefaultSort())->toBe(LibrarySort::FoldersThenName)
        ->and($plugin->getDefaultSortDirection())->toBe('asc');

    $names = $plugin
        ->applySort(LibraryItem::query()->where('parent_id', $parent->id))
        ->pluck('name')
        ->all();

    expect($names)->toBe(['Mango', 'Zebra', 'Apples', 'Bananas']);
});

test('folders stay first when the name direction is descending', function () {
    $userId = DB::table('users')->insertGetId([
        'name' => 'Librarian',
        'email' => 'librarian-desc@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $parent = librarySortItem($userId, 'Reference', 'folder');
    librarySortItem($userId, 'Mango', 'folder', $parent->id);
    librarySortItem($userId, 'Apples', 'file', $parent->id);
    librarySortItem($userId, 'Zebra', 'folder', $parent->id);
    librarySortItem($userId, 'Bananas', 'link', $parent->id);

    $names = FilamentLibraryPlugin::make()
        ->defaultSort(LibrarySort::FoldersThenName, 'desc')
        ->applySort(LibraryItem::query()->where('parent_id', $parent->id))
        ->pluck('name')
        ->all();

    expect($names)->toBe(['Zebra', 'Mango', 'Bananas', 'Apples']);
});

test('a column sort mixes folders and documents', function () {
    $userId = DB::table('users')->insertGetId([
        'name' => 'Librarian',
        'email' => 'librarian-name@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $parent = librarySortItem($userId, 'Reference', 'folder');
    librarySortItem($userId, 'Mango', 'folder', $parent->id);
    librarySortItem($userId, 'Apples', 'file', $parent->id);
    librarySortItem($userId, 'Bananas', 'link', $parent->id);

    $names = FilamentLibraryPlugin::make()
        ->defaultSort(LibrarySort::Name)
        ->applySort(LibraryItem::query()->where('parent_id', $parent->id))
        ->pluck('name')
        ->all();

    expect($names)->toBe(['Apples', 'Bananas', 'Mango']);
});

test('a closure can replace the library sort', function () {
    $userId = DB::table('users')->insertGetId([
        'name' => 'Librarian',
        'email' => 'librarian-closure@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    librarySortItem($userId, 'Alpha', 'file');
    librarySortItem($userId, 'Zulu', 'folder');

    $names = FilamentLibraryPlugin::make()
        ->defaultSort(fn (Builder $query): Builder => $query->orderBy('name', 'desc'))
        ->applySort(LibraryItem::query())
        ->pluck('name')
        ->all();

    expect($names)->toBe(['Zulu', 'Alpha']);
});

test('an empty library sort column is rejected', function () {
    FilamentLibraryPlugin::make()->defaultSort('   ');
})->throws(InvalidArgumentException::class);

function librarySortItem(int $userId, string $name, string $type, ?int $parentId = null): LibraryItem
{
    return LibraryItem::query()->create([
        'name' => $name,
        'type' => $type,
        'parent_id' => $parentId,
        'created_by' => $userId,
        'updated_by' => $userId,
        'general_access' => 'anyone_can_view',
    ]);
}
