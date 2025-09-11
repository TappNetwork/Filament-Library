<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Resources\Pages\EditRecord;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class EditLibraryItem extends EditRecord
{
    protected static string $resource = LibraryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
