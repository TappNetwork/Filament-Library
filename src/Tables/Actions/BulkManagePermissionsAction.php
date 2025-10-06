<?php

namespace Tapp\FilamentLibrary\Tables\Actions;

use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Illuminate\Database\Eloquent\Collection;
use Tapp\FilamentLibrary\Forms\Components\UserSearchSelect;
use Tapp\FilamentLibrary\Services\PermissionService;
use Tapp\FilamentLibrary\FilamentLibraryPlugin;

class BulkManagePermissionsAction extends BulkAction
{
    public static function getDefaultName(): string
    {
        return 'manage_permissions';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Manage Permissions')
            ->icon('heroicon-o-shield-check')
            ->color('warning')
            ->visible(fn (): bool => auth()->user() && FilamentLibraryPlugin::isLibraryAdmin(auth()->user()))
            ->form([
                Placeholder::make('general_access_section')
                    ->label('General Access')
                    ->content('Set the overall access level for these items'),

                Select::make('general_access')
                    ->label('General Access')
                    ->options([
                        'private' => 'Private (owner only)',
                        'anyone_can_view' => 'Anyone can view',
                    ])
                    ->default('private')
                    ->required()
                    ->helperText('This determines who can see these items by default'),

                Placeholder::make('user_permissions_section')
                    ->label('User Permissions')
                    ->content('Grant specific permissions to selected users'),

                UserSearchSelect::make('user_ids')
                    ->label('Users')
                    ->placeholder('Search for users by name or email...')
                    ->required()
                    ->helperText('Select users to grant permissions to'),

                Select::make('permission')
                    ->label('Permission Level for Selected Users')
                    ->options([
                        'view' => 'View Only',
                        'edit' => 'Edit',
                        'owner' => 'Owner',
                    ])
                    ->default('view')
                    ->required()
                    ->helperText('Choose the permission level to grant to the selected users above'),
            ])
            ->action(function (Collection $records, array $data) {
                $permissionService = app(PermissionService::class);
                $permissionService->bulkAssignPermissions($records, $data);

                $this->success();
            })
            ->deselectRecordsAfterCompletion()
            ->requiresConfirmation()
            ->modalHeading('Manage Permissions')
            ->modalDescription('Grant permissions to selected users for the chosen items.')
            ->modalSubmitActionLabel('Grant Permissions');
    }

    public function success(): void
    {
        $this->successNotification(
            title: 'Permissions Updated',
            body: 'Permissions have been successfully updated for the selected items.',
        );
    }
}
