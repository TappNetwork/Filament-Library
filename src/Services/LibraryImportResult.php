<?php

namespace Tapp\FilamentLibrary\Services;

use Tapp\FilamentLibrary\Models\LibraryItem;

class LibraryImportResult
{
    /**
     * @param  list<LibraryItem>  $items
     * @param  list<string>  $errors
     */
    public function __construct(
        public array $items = [],
        public array $errors = [],
    ) {}

    public function importedCount(): int
    {
        return count($this->items);
    }

    public function succeeded(): bool
    {
        return $this->errors === [];
    }
}
