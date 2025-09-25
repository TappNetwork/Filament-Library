<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class SearchAll extends ListRecords
{
    protected static string $resource = LibraryItemResource::class;

    protected static ?string $title = 'Search All';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getTableQuery();

        $user = auth()->user();
        if ($user) {
            // Show all items the user has access to
            $query->where(function ($q) use ($user) {
                // Items created by user
                $q->where('created_by', $user->id)
                // Items with explicit permissions
                ->orWhereHas('resourcePermissions', function ($permQuery) use ($user) {
                    $permQuery->where('user_id', $user->id);
                })
                // Public items
                ->orWhere('general_access', 'anyone_can_view');
            })
            ->where('name', 'not like', "%'s Personal Folder"); // Exclude personal folders
        } else {
            // For non-authenticated users, show only public items
            $query->where('general_access', 'anyone_can_view')
                ->where('name', 'not like', "%'s Personal Folder");
        }

        return $query;
    }

    public function getTitle(): string
    {
        return 'Search All';
    }

    public function getSubheading(): ?string
    {
        return 'All files and folders you have access to';
    }
}
