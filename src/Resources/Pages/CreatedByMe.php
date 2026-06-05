<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Tapp\FilamentLibrary\FilamentLibraryPlugin;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class CreatedByMe extends ListRecords
{
    protected static string $resource = LibraryItemResource::class;

    protected static ?string $title = 'Created by Me';

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        $user = auth()->user();
        if ($user) {
            $personalFolder = FilamentLibraryPlugin::libraryItemModelClass()::getPersonalFolder($user);
            $query->where('created_by', $user->id);

            // Exclude personal folder if it exists
            if ($personalFolder) {
                $query->where('id', '!=', $personalFolder->id);
            }
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public function getTitle(): string
    {
        return 'Created by Me';
    }

    public function getSubheading(): ?string
    {
        return 'Files and folders you created';
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl() => 'Library',
            '' => 'Created by Me',
        ];
    }
}
