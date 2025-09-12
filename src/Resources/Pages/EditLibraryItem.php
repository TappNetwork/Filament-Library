<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Resources\Pages\EditRecord;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;
use Filament\Actions\DeleteAction;

class EditLibraryItem extends EditRecord
{
    protected static string $resource = LibraryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Remove fields that shouldn't be editable
        unset($data['type']);
        unset($data['parent_id']);
        unset($data['created_by']);
        
        return $data;
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema(static::getResource()::editForm($this->makeFormSchema())->getComponents())
                    ->statePath('data')
                    ->model($this->getRecord())
            ),
        ];
    }
}
