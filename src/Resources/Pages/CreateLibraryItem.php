<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class CreateLibraryItem extends CreateRecord
{
    protected static string $resource = LibraryItemResource::class;
}
