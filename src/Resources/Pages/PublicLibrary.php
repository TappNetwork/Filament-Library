<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class PublicLibrary extends ListRecords
{
    protected static string $resource = LibraryItemResource::class;

    protected static ?string $title = 'Public Library';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getTableQuery();

        // Show only public items
        $query->where('general_access', 'anyone_can_view')
            ->where('name', 'not like', "%'s Personal Folder"); // Exclude personal folders

        return $query;
    }

    public function getTitle(): string
    {
        return 'Public Library';
    }

    public function getSubheading(): ?string
    {
        return 'Publicly accessible files and folders';
    }
}
