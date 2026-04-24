<?php

namespace Tapp\FilamentLibrary\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tapp\FilamentLibrary\Models\LibraryItem;

/**
 * Fired after a file was stored on a {@see LibraryItem} of type "file" (new Spatie media row).
 * Host applications may listen to run AI tagging, search indexing, etc.
 */
class LibraryFileStored
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public LibraryItem $libraryItem,
        public ?Media $media = null
    ) {}
}
