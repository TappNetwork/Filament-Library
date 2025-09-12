<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class CreateFolder extends CreateRecord
{
    protected static string $resource = LibraryItemResource::class;

    public ?int $parentId = null;

    public function mount(): void
    {
        parent::mount();
        
        $this->parentId = request()->get('parent');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'folder';
        $data['parent_id'] = $this->parentId;
        $data['created_by'] = auth()->user()?->id;
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->parentId 
            ? static::getResource()::getUrl('index', ['parent' => $this->parentId])
            : static::getResource()::getUrl('index');
    }
}
