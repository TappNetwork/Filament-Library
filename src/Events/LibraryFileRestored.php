<?php

namespace Tapp\FilamentLibrary\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tapp\FilamentLibrary\Models\LibraryItem;

/**
 * Fired after a soft-deleted {@see LibraryItem} of type "file" was restored.
 * Host applications may listen to run search indexing, RAG re-ingestion, etc.
 */
class LibraryFileRestored
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public LibraryItem $libraryItem,
        public ?Media $media = null
    ) {}
}
