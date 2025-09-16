<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class CreateLibraryItem extends CreateRecord
{
    protected static string $resource = LibraryItemResource::class;

    public ?int $parentId = null;

    public function mount(): void
    {
        parent::mount();

        $this->parentId = request()->get('parent');

        if ($this->parentId) {
            $this->form->fill([
                'parent_id' => $this->parentId,
            ]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($this->parentId) {
            $data['parent_id'] = $this->parentId;
        }

        $data['created_by'] = auth()->id();

        return $data;
    }
}
