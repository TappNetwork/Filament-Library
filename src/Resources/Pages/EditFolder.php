<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Forms\Components\Select;
use Tapp\FilamentLibrary\FilamentLibraryPlugin;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class EditFolder extends EditLibraryItemPage
{
    protected static string $resource = LibraryItemResource::class;

    protected ?int $parentId = null;

    public function getTitle(): string
    {
        return 'Edit Folder';
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                \Filament\Forms\Components\Textarea::make('link_description')
                    ->label('Description')
                    ->rows(3),

                Select::make('general_access')
                    ->label('General Access')
                    ->options(function () {
                        $options = \Tapp\FilamentLibrary\Models\LibraryItem::getGeneralAccessOptions();

                        // Remove inherit option if no parent folder
                        if (! $this->getRecord()->parent_id) {
                            unset($options['inherit']);
                        }

                        return $options;
                    })
                    ->default(function () {
                        // Default to inherit if has parent, otherwise private
                        return $this->getRecord()->parent_id ? 'inherit' : 'private';
                    })
                    ->helperText(function () {
                        $record = $this->getRecord();
                        $inherited = $record->getInheritedGeneralAccessDisplay();

                        $baseText = 'Set the baseline access level for this folder. User-level permissions can override this setting.';

                        if ($inherited) {
                            return $baseText . "\n\nCurrently inheriting: {$inherited}";
                        }

                        return $baseText;
                    })
                    ->visible(fn () => $this->getRecord()->hasPermission(auth()->user(), 'share')),

                Select::make('role_permissions')
                    ->label('Required Roles')
                    ->helperText('Only users with at least one of these roles can access this folder. Leave empty to allow all users (subject to other permissions).')
                    ->options(function () {
                        return FilamentLibraryPlugin::getAvailableRoles();
                    })
                    ->multiple()
                    ->searchable()
                    ->default(function () {
                        return $this->getRecord()->rolePermissions()->pluck('role_name')->toArray();
                    })
                    ->dehydrated(false) // Don't save to model directly, we handle it in mutateFormDataBeforeSave
                    ->visible(fn () => $this->getRecord()->hasPermission(auth()->user(), 'share')),

                // Creator select field
                $this->getCreatorSelectField(),
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle role permissions separately
        $rolePermissions = $data['role_permissions'] ?? [];
        unset($data['role_permissions']);

        return $data;
    }

    protected function afterSave(): void
    {
        parent::afterSave();

        $record = $this->getRecord();
        $rolePermissions = $this->form->getState()['role_permissions'] ?? [];

        // Sync role permissions
        $record->rolePermissions()->delete();
        if (is_array($rolePermissions) && ! empty($rolePermissions)) {
            foreach ($rolePermissions as $roleName) {
                $record->rolePermissions()->create([
                    'role_name' => $roleName,
                ]);
            }
        }
    }
}
