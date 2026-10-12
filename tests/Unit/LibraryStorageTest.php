<?php

use Tapp\FilamentLibrary\Support\LibraryStorage;

test('library files are not stored on the public disk', function (): void {
    config()->set('filament-library.filesystem_disk', 'public');
    config()->set('filesystems.default', 's3');

    expect(LibraryStorage::disk())->toBe('s3');

    config()->set('filament-library.filesystem_disk', null);
    config()->set('filesystems.default', 'public');

    expect(LibraryStorage::disk())->toBe('local');

    config()->set('filament-library.filesystem_disk', 's3');

    expect(LibraryStorage::disk())->toBe('s3');
});

test('library file previews use an application route', function (): void {
    $url = route('filament-library.files.show', ['media' => 11]);

    expect($url)->toBe(url('/library/files/11'))
        ->and($url)->not->toContain('/storage/');
});
