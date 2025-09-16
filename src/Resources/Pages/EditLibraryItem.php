<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class EditLibraryItem extends EditRecord
{
    protected static string $resource = LibraryItemResource::class;

    protected ?int $parentId = null;

    public function getTitle(): string
    {
        $record = $this->getRecord();
        $type = match ($record->type) {
            'folder' => 'Folder',
            'file' => 'File',
            'link' => 'External Link',
            default => 'Item',
        };

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
                ->url(
                    fn (): string => static::getResource()::getUrl('index', ['parent' => $this->getRecord()->parent_id])
                );
        }

        $actions[] = DeleteAction::make()
            ->before(function () {
                // Store parent_id before deletion
                $this->parentId = $this->getRecord()->parent_id;
            })
            ->successRedirectUrl(function () {
                // Redirect to the parent folder after deletion
                $parentId = $this->parentId;

                return static::getResource()::getUrl('index', $parentId ? ['parent' => $parentId] : []);
            });

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
            'form' => $this->form(static::getResource()::form(
                \Filament\Schemas\Schema::make()
            ))
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
