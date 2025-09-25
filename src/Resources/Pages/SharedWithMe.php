<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class SharedWithMe extends ListRecords
{
    protected static string $resource = LibraryItemResource::class;

    protected static ?string $title = 'Shared with Me';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getTableQuery();

        $user = auth()->user();
        if ($user) {
            // Show items where user has explicit permissions (not creator)
            $query->whereHas('resourcePermissions', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('created_by', '!=', $user->id); // Exclude items created by user
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public function getTitle(): string
    {
        return 'Shared with Me';
    }

    public function getSubheading(): ?string
    {
        return 'Files and folders shared with you by others';
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl() => 'Library',
            '' => 'Shared with Me',
        ];
    }
}
