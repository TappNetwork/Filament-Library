<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Tapp\FilamentLibrary\FilamentLibraryPlugin;
use Tapp\FilamentLibrary\Models\LibraryItem;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class GatekeeperQueue extends ListRecords
{
    protected static string $resource = LibraryItemResource::class;

    protected static ?string $title = 'Gatekeeper Queue';

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        $user = auth()->user();

        if (! $query instanceof Builder || ! $user || ! FilamentLibraryPlugin::isLibraryAdmin($user)) {
            return LibraryItem::query()->whereRaw('1 = 0');
        }

        return LibraryItem::limitToUnpublished($query);
    }

    public function getTitle(): string
    {
        return 'Gatekeeper Queue';
    }

    public function getSubheading(): ?string
    {
        return 'Draft, pending, and rejected items stay out of search until a gatekeeper publishes them.';
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl() => 'Library',
            '' => 'Gatekeeper Queue',
        ];
    }
}
