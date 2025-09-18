<?php

namespace Tapp\FilamentLibrary\Tables\Actions;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
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
            ->form([
                UserSearchSelect::make('user_ids')
                    ->label('Users')
                    ->placeholder('Search for users by name or email...')
                    ->required()
                    ->helperText('Select users to grant permissions to'),

                Select::make('permission')
                    ->label('Permission Level')
                    ->options([
                        'view' => 'View Only',
                        'edit' => 'Edit',
                    ])
                    ->default('view')
                    ->required()
                    ->helperText('Choose the permission level to grant'),

                Toggle::make('cascade_to_children')
                    ->label('Cascade to Children')
                    ->helperText('Apply these permissions to all items inside selected folders')
                    ->default(false),
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

    protected function success(): void
    {
        $this->successNotification(
            title: 'Permissions Updated',
            body: 'Permissions have been successfully updated for the selected items.',
        );
    }
}
