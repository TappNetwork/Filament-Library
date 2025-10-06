<?php

namespace Tapp\FilamentLibrary\Tables\Actions;

/**
 * NOTE: This bulk action is currently unused in the LibraryItemResource.
 * It was removed from the toolbar actions but kept for potential future use.
 */

use Filament\Actions\BulkAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Collection;
use Tapp\FilamentLibrary\FilamentLibraryPlugin;
use Tapp\FilamentLibrary\Forms\Components\UserSearchSelect;
use Tapp\FilamentLibrary\Services\PermissionService;

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
