<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;
use Tapp\FilamentLibrary\Models\LibraryItem;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;

class ListLibraryItems extends ListRecords
{
    protected static string $resource = LibraryItemResource::class;

    public ?int $parentId = null;
    public ?LibraryItem $parentFolder = null;

    public function mount(): void
    {
        parent::mount();
        
        $this->parentId = request()->get('parent');
        
        if ($this->parentId) {
            $this->parentFolder = LibraryItem::find($this->parentId);
        }
    }

    protected function getHeaderActions(): array
    {
        $actions = [];
        
        // Add "Up One Level" action if we're in a subfolder
        if ($this->parentId && $this->parentFolder) {
            $actions[] = Action::make('up_one_level')
                ->label('Up One Level')
                ->icon('heroicon-o-arrow-up')
                ->url(fn (): string => 
                    $this->parentFolder->parent_id 
                        ? static::getResource()::getUrl('index', ['parent' => $this->parentFolder->parent_id])
                        : static::getResource()::getUrl('index')
                )
                ->color('gray');
        }
        
        // Add "Create" action
        $actions[] = CreateAction::make()
            ->url(fn (): string => 
                $this->parentId 
                    ? static::getResource()::getUrl('create', ['parent' => $this->parentId])
                    : static::getResource()::getUrl('create')
            );
        
        return $actions;
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getTableQuery();
        
        if ($this->parentId) {
            $query->where('parent_id', $this->parentId);
        } else {
            $query->whereNull('parent_id');
        }
        
        return $query;
    }

    public function getTitle(): string
    {
        if ($this->parentFolder) {
            return $this->parentFolder->name;
        }
        
        return 'All Folders';
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            static::getResource()::getUrl() => 'All Folders',
        ];
        
        if ($this->parentFolder) {
            $current = $this->parentFolder;
            $path = [];
            
            while ($current) {
                array_unshift($path, $current);
                $current = $current->parent;
            }
            
            foreach ($path as $folder) {
                $breadcrumbs[static::getResource()::getUrl('index', ['parent' => $folder->id])] = $folder->name;
            }
        }
        
        return $breadcrumbs;
    }
}
