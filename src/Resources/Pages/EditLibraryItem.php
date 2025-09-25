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

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Automatically redirect to the correct edit page based on type
        $record = $this->getRecord();
        $editUrl = static::getResource()::getEditUrl($record);

        $this->redirect($editUrl);
    }

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

        // Add "Up One Level" action if we have a parent
        if ($this->getRecord()->parent_id) {
            $actions[] = Action::make('up_one_level')
                ->label('Up One Level')
                ->icon('heroicon-o-arrow-up')
                ->color('gray')
                ->url(
                    fn (): string => static::getResource()::getUrl('index', ['parent' => $this->getRecord()->parent_id])
                );
        }

        // Add "View" action - for folders go to list page, for files/links go to view page
        $viewUrl = $this->getRecord()->type === 'folder'
            ? static::getResource()::getUrl('index', ['parent' => $this->getRecord()->id])
            : static::getResource()::getUrl('view', ['record' => $this->getRecord()->id]);

        $actions[] = Action::make('view')
            ->label('View')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->url($viewUrl);

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

    // NOTE: This page is a redirect middleware only - it never displays a form
    // Forms are defined in the specific edit pages:
    // - EditFolder.php (for folders)
    // - EditFile.php (for files)
    // - EditLink.php (for external links)
    // This page automatically redirects to the appropriate edit page based on item type

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            static::getResource()::getUrl() => 'Library',
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
