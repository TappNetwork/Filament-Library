<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Resources\Pages\EditRecord;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Tapp\FilamentLibrary\Models\LibraryItem;

class EditLibraryItem extends EditRecord
{
    protected static string $resource = LibraryItemResource::class;

    public function getTitle(): string
    {
        $record = $this->getRecord();
        $type = $record->type === 'folder' ? 'Folder' : 'File';
        return "Edit {$type}";
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

        $actions[] = Action::make('move')
                ->label('Move')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('warning')
                ->form([
                    Select::make('parent_id')
                        ->label('Move to folder')
                        ->options(function () {
                            $currentId = $this->getRecord()->id;

                            return LibraryItem::where('type', 'folder')
                                ->where('id', '!=', $currentId)
                                ->where(function ($query) use ($currentId) {
                                    // Prevent moving into self or descendants
                                    $query->whereNull('parent_id')
                                          ->orWhere('parent_id', '!=', $currentId);
                                })
                                ->pluck('name', 'id')
                                ->prepend('Root (No parent)', null);
                        })
                        ->searchable()
                        ->preload()
                        ->default($this->getRecord()->parent_id),
                ])
                ->action(function (array $data): void {
                    $this->getRecord()->update([
                        'parent_id' => $data['parent_id'],
                        'updated_by' => auth()->user()?->id,
                    ]);

                    $this->redirect(static::getResource()::getUrl('index', $data['parent_id'] ? ['parent' => $data['parent_id']] : []));
                });

        $actions[] = DeleteAction::make();

        return $actions;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Remove fields that shouldn't be editable
        unset($data['type']);
        unset($data['parent_id']);
        unset($data['created_by']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Set the updated_by field
        $data['updated_by'] = auth()->user()?->id;

        return $data;
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->makeForm()
                ->schema([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                ])
                ->statePath('data')
                ->model($this->getRecord()),
        ];
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            static::getResource()::getUrl() => 'All Folders',
        ];

        $record = $this->getRecord();

        if ($record->parent_id) {
            // Cache the breadcrumb path to avoid repeated computation
            $cacheKey = 'breadcrumbs_' . $record->parent_id;
            $path = cache()->remember($cacheKey, 300, function () use ($record) { // 5 minute cache
                $current = $record->parent;
                $path = [];

                while ($current) {
                    array_unshift($path, $current);
                    $current = $current->parent;
                }

                return $path;
            });

            // Generate URLs more efficiently
            $baseUrl = static::getResource()::getUrl('index');
            foreach ($path as $folder) {
                $breadcrumbs[$baseUrl . '?parent=' . $folder->id] = $folder->name;
            }
        }

        // Add current item to breadcrumbs
        $breadcrumbs[] = $record->name;

        return $breadcrumbs;
    }

}
