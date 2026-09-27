<?php

namespace Tapp\FilamentLibrary\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Tapp\FilamentLibrary\Models\LibraryItem;

/**
 * Fired when a gatekeeper publishes a library item.
 * Host applications may listen to update a search index. Drafts are not published by import.
 */
class LibraryItemPublished
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public LibraryItem $libraryItem,
    ) {}
}
