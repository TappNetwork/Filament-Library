<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class Favorites extends ListRecords
{
    protected static string $resource = LibraryItemResource::class;

    protected static ?string $title = 'Favorites';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getTableQuery();

        $user = auth()->user();
        if ($user) {
            // Show items that the user has favorited
            $query->whereHas('favoritedBy', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public function getTitle(): string
    {
        return 'Favorites';
    }

    public function getSubheading(): ?string
    {
        return 'Your favorite files and folders';
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl() => 'Library',
            '' => 'Favorites',
        ];
    }

    public function getComponentName(): string
    {
        return 'tapp.filament-library.resources.pages.favorites';
    }
}
