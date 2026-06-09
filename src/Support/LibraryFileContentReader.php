<?php

declare(strict_types=1);

namespace Tapp\FilamentLibrary\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class LibraryFileContentReader
{
    public static function read(Media $media): ?string
    {
        $maxBytes = (int) config('filament-library.preview.text_max_bytes', 2 * 1024 * 1024);

        if ($media->size > $maxBytes) {
            return null;
        }

        try {
            $path = $media->getPath();

            if (! is_readable($path)) {
                return null;
            }

            $content = file_get_contents($path, false, null, 0, $maxBytes + 1);

            if ($content === false || strlen($content) > $maxBytes) {
                return null;
            }

            return $content;
        } catch (\Throwable) {
            return null;
        }
    }
}
