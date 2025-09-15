<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Resources\Pages\ViewRecord;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;
use Filament\Actions\Action;

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
        $actions = [];

        // Add "View Folder" action if we have a parent
        if ($this->getRecord()->parent_id) {
            $actions[] = Action::make('view_folder')
                ->label('View Folder')
                ->icon('heroicon-o-arrow-up')
                ->color('gray')
                ->url(fn (): string =>
                    static::getResource()::getUrl('index', ['parent' => $this->getRecord()->parent_id])
                );
        }

        $actions[] = \Filament\Actions\EditAction::make();
        $actions[] = \Filament\Actions\DeleteAction::make();

        return $actions;
    }
}
