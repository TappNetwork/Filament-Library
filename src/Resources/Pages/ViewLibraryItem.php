<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Resources\Pages\ViewRecord;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class ViewLibraryItem extends ViewRecord
{
    protected static string $resource = LibraryItemResource::class;

    public function getTitle(): string
    {
        $record = $this->getRecord();
        $type = $record->type === 'folder' ? 'Folder' : 'File';
        return "View {$type}";
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
