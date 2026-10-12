<?php

namespace Tapp\FilamentLibrary\Support;

use Filament\Forms\Components\FileUpload;
use Tapp\FilamentLibrary\Models\LibraryItem;

class LibraryStorage
{
    /**
     * Disk that keeps library files private.
     *
     * The public disk is rejected so previews are not served from /storage.
     */
    public static function disk(): string
    {
        $configured = config('filament-library.filesystem_disk');

        if (is_string($configured) && $configured !== '' && $configured !== 'public') {
            return $configured;
        }

        $default = config('filesystems.default');

        if (is_string($default) && $default !== '' && $default !== 'public') {
            return $default;
        }

        return 'local';
    }

    public static function uploadField(string $name = 'file'): FileUpload
    {
        $disk = static::disk();

        $upload = FileUpload::make($name)
            ->label('Upload File')
            ->required()
            ->maxSize(512000)
            ->disk($disk)
            ->directory('library-files')
            ->preserveFilenames();

        if (config("filesystems.disks.{$disk}.driver") === 'local') {
            $upload->visibility('private');
        }

        return $upload;
    }

    public static function attachUploadedFile(LibraryItem $libraryItem, string $storedPath): void
    {
        $disk = static::disk();
        $fileName = basename($storedPath);

        $libraryItem->addMediaFromDisk($storedPath, $disk)
            ->usingName($fileName)
            ->usingFileName($fileName)
            ->toMediaCollection('default', $disk);
    }
}
