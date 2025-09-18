<?php

namespace Tapp\FilamentLibrary\Tables\Columns;

use Filament\Tables\Columns\Column;
use Tapp\FilamentLibrary\Models\LibraryItem;
use Tapp\FilamentLibrary\Services\PermissionService;

class PermissionsColumn extends Column
{
    protected string $view = 'filament-library::tables.columns.permissions-column';

    public static function make(?string $name = null): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Permissions')
            ->sortable(false)
            ->searchable(false)
            ->getStateUsing(function (LibraryItem $record): array {
                $permissionService = app(PermissionService::class);
                $users = $permissionService->getUsersWithPermissions($record);

                return $users->map(function ($user) use ($record, $permissionService) {
                    $permissions = $permissionService->getUserPermissions($user, $record);

                    return [
                        'user' => $user,
                        'permissions' => $permissions,
                        'is_creator' => $record->created_by === $user->id,
                    ];
                })->toArray();
            });
    }
}
