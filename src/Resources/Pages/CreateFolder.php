<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Forms\Components\Select;
use Filament\Resources\Pages\CreateRecord;
use Tapp\FilamentLibrary\FilamentLibraryPlugin;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;
use Tapp\FilamentLibrary\Traits\HasParentFolder;

class CreateFolder extends CreateRecord
{
    use HasParentFolder;

    protected static string $resource = LibraryItemResource::class;

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                ...LibraryItemResource::folderForm($schema)->getComponents(),
                Select::make('role_permissions')
                    ->label('Required Roles')
                    ->helperText('Only users with at least one of these roles can access this folder. Leave empty to allow all users (subject to other permissions).')
                    ->options(function () {
                        return FilamentLibraryPlugin::getAvailableRoles();
                    })
                    ->multiple()
                    ->searchable()
                    ->dehydrated(false) // Don't save to model directly, we handle it in afterCreate
                    ->visible(fn () => auth()->user() && \Tapp\FilamentLibrary\FilamentLibraryPlugin::isLibraryAdmin(auth()->user())),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'folder';
        $data['parent_id'] = $this->getParentId();
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        parent::afterCreate();

        $record = $this->getRecord();
        $rolePermissions = $this->form->getState()['role_permissions'] ?? [];

        // Sync role permissions
        if (is_array($rolePermissions) && ! empty($rolePermissions)) {
            foreach ($rolePermissions as $roleName) {
                $record->rolePermissions()->create([
                    'role_name' => $roleName,
                ]);
            }
        }
    }
}
