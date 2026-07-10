<?php

namespace Tapp\FilamentLibrary\Tables\Actions;

use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Tapp\FilamentLibrary\FilamentLibraryPlugin;
use Tapp\FilamentLibrary\Services\PermissionService;
use Tapp\FilamentLibrary\Support\UserAssignmentFilters;

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
            ->schema([
                Select::make('general_access')
                    ->label('General Access')
                    ->options([
                        'private' => 'Private (owner only)',
                        'anyone_can_view' => 'Anyone can view',
                    ])
                    ->default('private')
                    ->required()
                    ->helperText('This determines who can see these items by default'),
                ...UserAssignmentFilters::schema('user_ids'),
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
            ->action(function (Collection $records, array $data): void {
                $permissionService = app(PermissionService::class);
                $permissionService->bulkAssignPermissions($records, $data);

                $this->success();
            })
            ->deselectRecordsAfterCompletion()
            ->requiresConfirmation()
            ->modalHeading('Manage Permissions')
            ->modalDescription('Filter users, then grant permissions for the selected items.')
            ->modalSubmitActionLabel('Grant Permissions');
    }

    public function success(): void
    {
        Notification::make()
            ->title('Permissions Updated')
            ->body('Permissions have been successfully updated for the selected items.')
            ->success()
            ->send();
    }
}
