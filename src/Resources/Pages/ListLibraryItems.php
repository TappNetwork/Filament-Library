<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class ListLibraryItems extends ListRecords
{
    protected static string $resource = LibraryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
