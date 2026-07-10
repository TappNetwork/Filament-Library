<?php

namespace Tapp\FilamentLibrary\Resources\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Tapp\FilamentLibrary\FilamentLibraryPlugin;
use Tapp\FilamentLibrary\Models\LibraryItem;
use Tapp\FilamentLibrary\Services\PermissionService;
use Tapp\FilamentLibrary\Support\UserAssignmentFilters;

class LibraryItemPermissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'permissions';

    protected static ?string $title = 'User Permissions';

    protected static ?string $modelLabel = 'Permission';

    protected static ?string $pluralModelLabel = 'Permissions';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        $record = static::getOwnerRecord();

        /** @var LibraryItem $record */
        return $record->hasPermission($user, 'share');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        /** @var LibraryItem $ownerRecord */
        return $ownerRecord->hasPermission(auth()->user(), 'share');
    }

    /**
     * Get the display name for a user, supporting both 'name' and 'first_name/last_name' fields.
     */
    private function getUserDisplayName($user): string
    {
        if (! $user) {
            return 'Unknown User';
        }

        if ($user->name) {
            return $user->name . ' (' . $user->email . ')';
        }

        if (SchemaFacade::hasColumn('users', 'first_name') && SchemaFacade::hasColumn('users', 'last_name')) {
            $firstName = $user->first_name ?? '';
            $lastName = $user->last_name ?? '';
            $fullName = trim($firstName . ' ' . $lastName);

            if ($fullName) {
                return $fullName . ' (' . $user->email . ')';
            }
        }

        return $user->email;
    }

    public function form(Schema $schema): Schema
    {
        $userModel = UserAssignmentFilters::userModel();

        return $schema
            ->components([
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->searchable()
                    ->preload()
                    ->getSearchResultsUsing(
                        fn (string $search): array => $userModel::where(function ($query) use ($search): void {
                            if (SchemaFacade::hasColumn('users', 'first_name') && SchemaFacade::hasColumn('users', 'last_name')) {
                                $query->orWhere('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            } elseif (SchemaFacade::hasColumn('users', 'name')) {
                                $query->orWhere('name', 'like', "%{$search}%");
                            }

                            $query->orWhere('email', 'like', "%{$search}%");
                        })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn ($user) => [
                                $user->id => $this->getUserDisplayName($user),
                            ])
                            ->toArray()
                    )
                    ->getOptionLabelUsing(
                        fn ($value): ?string => $this->getUserDisplayName($userModel::find($value))
                    )
                    ->required(),

                Forms\Components\Select::make('role')
                    ->label('Role')
                    ->options(FilamentLibraryPlugin::libraryItemPermissionModelClass()::getRoleOptions())
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        $filterBased = (bool) config('filament-library.permissions.filter_based_assignment', false);

        return $table
            ->recordTitleAttribute('role')
            ->columns([
                Tables\Columns\TextColumn::make('user')
                    ->label('User')
                    ->formatStateUsing(fn ($record) => $this->getUserDisplayName($record->user))
                    ->searchable(function ($query, $search) {
                        return $query->where(function ($query) use ($search): void {
                            if (SchemaFacade::hasColumn('users', 'first_name') && SchemaFacade::hasColumn('users', 'last_name')) {
                                $query->orWhere('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            } elseif (SchemaFacade::hasColumn('users', 'name')) {
                                $query->orWhere('name', 'like', "%{$search}%");
                            }

                            $query->orWhere('email', 'like', "%{$search}%");
                        });
                    }),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'owner' => 'danger',
                        'editor' => 'warning',
                        'viewer' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options(FilamentLibraryPlugin::libraryItemPermissionModelClass()::getRoleOptions()),
            ])
            ->headerActions([
                $filterBased
                    ? $this->filterBasedCreateAction()
                    : $this->classicCreateAction(),
            ])
            ->heading('User Permissions')
            ->description(
                $filterBased
                    ? 'Filter and assign users in bulk. Owner: Share and edit. Editor/Viewer: Standard permissions.'
                    : 'Owner: Share and edit. Editor/Viewer: Standard permissions.'
            )
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => $this->canShareOwnerRecord()),
                DeleteAction::make()
                    ->visible(fn (): bool => $this->canShareOwnerRecord()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => $this->canShareOwnerRecord()),
                ]),
            ])
            ->emptyStateHeading('No permissions assigned')
            ->emptyStateDescription(
                $filterBased
                    ? 'Filter users and assign permissions to grant access to this item.'
                    : 'Add users to grant them specific permissions on this item.'
            )
            ->emptyStateActions([
                $filterBased
                    ? $this->filterBasedCreateAction()->label('Assign Users')
                    : $this->classicCreateAction()->label('Add Permission'),
            ]);
    }

    protected function classicCreateAction(): CreateAction
    {
        return CreateAction::make()
            ->visible(fn (): bool => $this->canShareOwnerRecord());
    }

    protected function filterBasedCreateAction(): CreateAction
    {
        return CreateAction::make()
            ->label('Assign Users')
            ->modalHeading('Assign User Permissions')
            ->modalDescription('Filter users by community, user level, and sign-up date, then assign permissions in bulk.')
            ->schema([
                ...UserAssignmentFilters::schema('user_ids'),
                Forms\Components\Select::make('role')
                    ->label('Permission Role')
                    ->options(FilamentLibraryPlugin::libraryItemPermissionModelClass()::getRoleOptions())
                    ->required()
                    ->default('viewer'),
            ])
            ->using(function (array $data, string $model): Model {
                /** @var LibraryItem $ownerRecord */
                $ownerRecord = $this->getOwnerRecord();
                $permissionService = app(PermissionService::class);
                $permission = match ($data['role'] ?? 'viewer') {
                    'owner' => 'owner',
                    'editor' => 'edit',
                    default => 'view',
                };

                $permissionService->bulkAssignPermissions([$ownerRecord], [
                    'user_ids' => $data['user_ids'] ?? [],
                    'permission' => $permission,
                    'general_access' => $ownerRecord->general_access ?? 'private',
                ]);

                $firstUserId = collect($data['user_ids'] ?? [])->first();

                return $ownerRecord->permissions()
                    ->where('user_id', $firstUserId)
                    ->firstOrFail();
            })
            ->successNotification(
                Notification::make()
                    ->success()
                    ->title('Permissions assigned')
                    ->body('Selected users were granted access to this item.')
            )
            ->visible(fn (): bool => $this->canShareOwnerRecord());
    }

    protected function canShareOwnerRecord(): bool
    {
        /** @var LibraryItem $ownerRecord */
        $ownerRecord = $this->ownerRecord;

        return $ownerRecord->hasPermission(auth()->user(), 'share');
    }
}
