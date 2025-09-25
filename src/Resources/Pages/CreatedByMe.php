<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class CreatedByMe extends ListRecords
{
    protected static string $resource = LibraryItemResource::class;

    protected static ?string $title = 'Created by Me';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getTableQuery();

        $user = auth()->user();
        if ($user) {
            $query->where('created_by', $user->id);
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
