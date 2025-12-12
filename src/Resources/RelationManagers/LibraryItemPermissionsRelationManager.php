<?php

namespace Tapp\FilamentLibrary\Resources\RelationManagers;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Tapp\FilamentLibrary\Models\LibraryItem;
use Tapp\FilamentLibrary\Models\LibraryItemPermission;

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

        // Admins can always access
        if ($user->hasRole('Admin')) {
            return true;
        }

        // For non-admins, check if they have share permission on the current record
        $record = static::getOwnerRecord();
        if ($record instanceof LibraryItem && $record->hasPermission($user, 'share')) {
            return true;
        }

        return false;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof LibraryItem && $ownerRecord->hasPermission(auth()->user(), 'share');
    }

    /**
     * Get the display name for a user, supporting both 'name' and 'first_name/last_name' fields.
     */
    private function getUserDisplayName($user): string
    {
        if (! $user) {
            return 'Unknown User';
        }

        // Check if user has a name accessor or name field with a value
        if ($user->name) {
            return $user->name . ' (' . $user->email . ')';
        }

        // Fall back to first_name/last_name if available
        if (SchemaFacade::hasColumn('users', 'first_name') && SchemaFacade::hasColumn('users', 'last_name')) {
            $firstName = $user->first_name ?? '';
            $lastName = $user->last_name ?? '';
            $fullName = trim($firstName . ' ' . $lastName);

            if ($fullName) {
                return $fullName . ' (' . $user->email . ')';
            }
        }

        // Fall back to email only
        return $user->email;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->searchable()
                    ->preload()
                    ->getSearchResultsUsing(
                        fn (string $search): array => User::where(function ($query) use ($search) {
                            // Search first_name and last_name fields if they exist
                            if (SchemaFacade::hasColumn('users', 'first_name') && SchemaFacade::hasColumn('users', 'last_name')) {
                                $query->orWhere('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            }
                            // Search name field if it exists and first/last don't
                            elseif (SchemaFacade::hasColumn('users', 'name')) {
                                $query->orWhere('name', 'like', "%{$search}%");
                            }
                            // Always search email
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
                        fn ($value): ?string => $this->getUserDisplayName(User::find($value))
                    )
                    ->required(),

                Forms\Components\Select::make('role')
                    ->label('Role')
                    ->options(LibraryItemPermission::getRoleOptions())
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('role')
            ->columns([
                Tables\Columns\TextColumn::make('user')
                    ->label('User')
                    ->formatStateUsing(fn ($record) => $this->getUserDisplayName($record->user))
                    ->searchable(function ($query, $search) {
                        return $query->where(function ($query) use ($search) {
                            // Search first_name and last_name fields if they exist
                            if (SchemaFacade::hasColumn('users', 'first_name') && SchemaFacade::hasColumn('users', 'last_name')) {
                                $query->orWhere('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            }
                            // Search name field if it exists and first/last don't
                            elseif (SchemaFacade::hasColumn('users', 'name')) {
                                $query->orWhere('name', 'like', "%{$search}%");
                            }
                            // Always search email
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
                    ->options(LibraryItemPermission::getRoleOptions()),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn () => $this->ownerRecord instanceof LibraryItem && $this->ownerRecord->hasPermission(auth()->user(), 'share')),
            ])
            ->heading('User Permissions')
            ->description('Owner: Share and edit. Editor/Viewer: Standard permissions.')
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => $this->ownerRecord instanceof LibraryItem && $this->ownerRecord->hasPermission(auth()->user(), 'share')),
                DeleteAction::make()
                    ->visible(fn () => $this->ownerRecord instanceof LibraryItem && $this->ownerRecord->hasPermission(auth()->user(), 'share')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => $this->ownerRecord instanceof LibraryItem && $this->ownerRecord->hasPermission(auth()->user(), 'share')),
                ]),
            ])
            ->emptyStateHeading('No permissions assigned')
            ->emptyStateDescription('Add users to grant them specific permissions on this item.')
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Add Permission')
                    ->visible(fn () => $this->ownerRecord instanceof LibraryItem && $this->ownerRecord->hasPermission(auth()->user(), 'share')),
            ]);
    }
}
