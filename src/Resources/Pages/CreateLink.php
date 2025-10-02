<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;
use Tapp\FilamentLibrary\Traits\HasParentFolder;

class CreateLink extends CreateRecord
{
    use HasParentFolder;

    protected static string $resource = LibraryItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'link';
        $data['parent_id'] = $this->getParentId();
        $data['created_by'] = auth()->id();

        return $data;
    }
}




