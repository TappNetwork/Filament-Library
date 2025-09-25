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
        $user = auth()->user();

        // Show public items
        $query->where(function ($q) use ($user) {
            $q->where('general_access', 'anyone_can_view');

            // Admins can see all items (including private/inherit)
            if ($user && \Tapp\FilamentLibrary\FilamentLibraryPlugin::isLibraryAdmin($user)) {
                $q->orWhere(function ($adminQuery) {
                    $adminQuery->whereIn('general_access', ['private', 'inherit'])
                        ->whereNull('parent_id'); // Only root-level items
                });
            }

            // Creators can see their own items (even if private)
            if ($user) {
                $q->orWhere('created_by', $user->id);
            }
        })
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

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl() => 'Library',
            '' => 'Public Library',
        ];
    }
}
