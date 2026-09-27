<?php

namespace Tapp\FilamentLibrary\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Tapp\FilamentLibrary\Models\LibraryItem;

/**
 * Fired when a gatekeeper rejects a library item.
 * Rejected items stay out of search. Host applications may listen to drop an index entry.
 */
class LibraryItemRejected
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public LibraryItem $libraryItem,
        public ?string $reason = null,
    ) {}
}
