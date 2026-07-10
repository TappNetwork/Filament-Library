<?php

namespace Tapp\FilamentLibrary\Resources;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Tapp\FilamentLibrary\FilamentLibraryPlugin;
use Tapp\FilamentLibrary\Models\LibraryItem;
use Tapp\FilamentLibrary\Resources\RelationManagers\LibraryItemPermissionsRelationManager;
use Tapp\FilamentLibrary\Tables\Actions\BulkManagePermissionsAction;

class LibraryItemResource extends Resource
{
    protected static ?string $model = LibraryItem::class;

    /**
     * @return class-string<LibraryItem>
     */
    public static function getModel(): string
    {
        return FilamentLibraryPlugin::libraryItemModelClass();
    }

    protected static ?int $deletedParentId = null;

    protected static ?string $slug = 'library';

    /**
     * Determine if the resource is scoped to a tenant.
     */
    public static function isScopedToTenant(): bool
    {
        return config('filament-library.tenancy.enabled', false);
    }

    /**
     * Get the name of the tenant ownership relationship.
     */
    public static function getTenantOwnershipRelationshipName(): string
    {
        if (! config('filament-library.tenancy.enabled')) {
            return 'tenant';
        }

        return static::getModel()::getTenantRelationshipName();
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-folder';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Don't show in navigation - we use custom navigation items
    }

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public static function getNavigationSort(): ?int
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return 'Library Items';
    }

    protected static ?string $modelLabel = 'Library Item';

    protected static ?string $pluralModelLabel = 'Library Items';

    public static function getModelLabel(): string
    {
        // For now, return a generic label since we can't access record context here
        // The dynamic labels will be handled at the page level where we have record access
        return static::$modelLabel ?? 'Library Item';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Library';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Select::make('type')
                    ->options([
                        'folder' => 'Folder',
                        'file' => 'File',
                        'link' => 'External Link',
                    ])
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('external_url', null)),

                // Folder form fields
                Textarea::make('link_description')
                    ->label('Description')
                    ->visible(fn (callable $get) => $get('type') === 'folder')
                    ->rows(3),

                // File form fields
                SpatieMediaLibraryFileUpload::make('files')
                    ->label('File')
                    ->visible(fn (callable $get) => $get('type') === 'file')
                    ->required(fn (callable $get) => $get('type') === 'file')
                    ->maxSize(512000), // 500MB

                // Link form fields
                TextInput::make('external_url')
                    ->label('URL')
                    ->url()
                    ->visible(fn (callable $get) => $get('type') === 'link')
                    ->required(fn (callable $get) => $get('type') === 'link'),

                Textarea::make('link_description')
                    ->label('Description')
                    ->visible(fn (callable $get) => $get('type') === 'link')
                    ->rows(3),
            ]);
    }

    public static function folderForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Folder Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Enter folder name'),
            ]);
    }

    public static function table(Table $table): Table
    {
        // This table configuration is used by ALL list pages:
        // - ListLibraryItems (main library view)
        // - PublicLibrary (public files)
        // - MyLibrary (personal documents)
        // - CreatedByMe (user's created items)
        // - SearchAll (search results)
        // The list pages only override getTableQuery() for filtering, not the columns
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 50 ? $state : null;
                    })
                    ->icon(fn (?LibraryItem $record): string => $record?->getDisplayIcon() ?? 'heroicon-o-document')
                    ->iconPosition('before')
                    ->description(function (?LibraryItem $record): ?string {
                        $user = auth()->user();
                        if (! $user || ! $record) {
                            return null;
                        }

                        if ($record->created_by !== $user->id && $record->permissions()->where('user_id', $user->id)->exists()) {
                            return 'Shared with you';
                        }

                        return null;
                    })
                    ->url(function (?LibraryItem $record): ?string {
                        if (! $record) {
                            return null;
                        }

                        return match ($record->type) {
                            'folder' => static::getUrl('index', ['parent' => $record->id]),
                            'link' => static::getUrl('view', ['record' => $record]),
                            'file' => static::getUrl('view', ['record' => $record]),
                            default => null,
                        };
                    }),
                Tables\Columns\ViewColumn::make('tags')
                    ->label('Tags')
                    ->view('filament-library::tables.columns.tags-column')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updater.name')
                    ->label('Modified By')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modified At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('external_url')
                    ->label('URL')
                    ->visible(fn (?LibraryItem $record) => $record && $record->type === 'link')
                    ->limit(50)
                    ->tooltip(fn (?LibraryItem $record) => $record?->external_url)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('general_access')
                    ->label('Permissions')
                    ->badge()
                    ->color(function (string $state, ?LibraryItem $record): string {
                        $effectiveAccess = $record?->getEffectiveAccessControl() ?? $state;

                        return match ($effectiveAccess) {
                            'private' => 'danger',
                            'anyone_can_view' => 'success',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function (string $state, ?LibraryItem $record): string {
                        $effectiveAccess = $record?->getEffectiveAccessControl() ?? $state;

                        return match ($effectiveAccess) {
                            'private' => 'Private',
                            'anyone_can_view' => 'Public',
                            default => ucfirst(str_replace('_', ' ', $effectiveAccess)),
                        };
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_favorite')
                    ->label('Favorite')
                    ->icon(fn (bool $state): string => $state ? 'heroicon-s-star' : 'heroicon-o-star')
                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray')
                    ->action(function (LibraryItem $record): void {
                        $record->toggleFavorite();
                    })
                    ->tooltip(fn (bool $state): string => $state ? 'Remove from favorites' : 'Add to favorites')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'folder' => 'Folder',
                        'file' => 'File',
                        'link' => 'External Link',
                    ]),
                Tables\Filters\SelectFilter::make('tags')
                    ->label('Tags')
                    ->relationship('tags', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->visible(fn (LibraryItem $record): bool => auth()->user() && $record->hasPermission(auth()->user(), 'view'))
                        ->url(function (LibraryItem $record): string {
                            // Use the same logic as recordUrl - cache URL generation to reduce computation
                            $cacheKey = 'record_url_' . $record->id . '_' . $record->type;

                            return cache()->remember($cacheKey, 60, function () use ($record) { // 1 minute cache
                                return $record->type === 'folder'
                                    ? static::getUrl('index', ['parent' => $record->id])
                                    : static::getUrl('view', ['record' => $record]);
                            });
                        }),
                    EditAction::make()
                        ->color('gray')
                        ->visible(fn (LibraryItem $record): bool => auth()->user() && $record->hasPermission(auth()->user(), 'edit'))
                        ->url(function (LibraryItem $record): string {
                            return static::getEditUrl($record);
                        }),
                    DeleteAction::make()
                        ->color('gray')
                        ->visible(fn (LibraryItem $record): bool => auth()->user() && $record->hasPermission(auth()->user(), 'delete'))
                        ->before(function (LibraryItem $record) {
                            // Store parent_id before deletion
                            static::$deletedParentId = $record->parent_id;
                        })
                        ->successRedirectUrl(function () {
                            // Redirect to the parent folder after deletion
                            $parentId = static::$deletedParentId;

                            return static::getUrl('index', $parentId ? ['parent' => $parentId] : []);
                        }),
                    RestoreAction::make()
                        ->visible(fn (LibraryItem $record): bool => auth()->user() && $record->hasPermission(auth()->user(), 'delete') && $record->deleted_at !== null),
                    ForceDeleteAction::make()
                        ->visible(fn (LibraryItem $record): bool => auth()->user() && $record->hasPermission(auth()->user(), 'delete') && $record->deleted_at !== null)
                        ->before(function (LibraryItem $record) {
                            // Store parent_id before deletion
                            static::$deletedParentId = $record->parent_id;
                        })
                        ->successRedirectUrl(function () {
                            // Redirect to the parent folder after deletion
                            $parentId = static::$deletedParentId;

                            return static::getUrl('index', $parentId ? ['parent' => $parentId] : []);
                        }),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ...static::bulkManagePermissionsActions(),
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user() && auth()->user()->can('deleteAny', static::getModel()))
                        ->successRedirectUrl(function () {
                            // For bulk actions, redirect to current folder (maintain current location)
                            $currentParent = request()->get('parent');

                            return static::getUrl('index', $currentParent ? ['parent' => $currentParent] : []);
                        }),
                    RestoreBulkAction::make()
                        ->visible(fn (): bool => auth()->user() && auth()->user()->can('restoreAny', static::getModel())),
                    ForceDeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user() && auth()->user()->can('forceDeleteAny', static::getModel()))
                        ->successRedirectUrl(function () {
                            // For bulk actions, redirect to current folder (maintain current location)
                            $currentParent = request()->get('parent');

                            return static::getUrl('index', $currentParent ? ['parent' => $currentParent] : []);
                        }),
                ]),
            ])
            ->recordUrl(function (LibraryItem $record): string {
                // Cache URL generation to reduce computation during rapid navigation
                $cacheKey = 'record_url_' . $record->id . '_' . $record->type;

                return cache()->remember($cacheKey, 60, function () use ($record) { // 1 minute cache
                    return $record->type === 'folder'
                        ? static::getUrl('index', ['parent' => $record->id])
                        : static::getUrl('view', ['record' => $record]);
                });
            });
    }

    public static function getRelations(): array
    {
        return [
            LibraryItemPermissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLibraryItems::route('/'),
            'my-documents' => Pages\MyLibrary::route('/my-documents'),
            ...static::sharedWithMePage(),
            'created-by-me' => Pages\CreatedByMe::route('/created-by-me'),
            'favorites' => Pages\Favorites::route('/favorites'),
            'public' => Pages\PublicLibrary::route('/public'),
            'search-all' => Pages\SearchAll::route('/search-all'),
            'create-folder' => Pages\CreateFolder::route('/create-folder'),
            'create-file' => Pages\CreateFile::route('/create-file'),
            'create-link' => Pages\CreateLink::route('/create-link'),
            'view' => Pages\ViewLibraryItem::route('/{record}'),
            'edit' => Pages\EditLibraryItem::route('/{record}/edit'), // Keep old route for middleware redirect
            'edit-folder' => Pages\EditFolder::route('/{record}/edit-folder'),
            'edit-file' => Pages\EditFile::route('/{record}/edit-file'),
            'edit-link' => Pages\EditLink::route('/{record}/edit-link'),
        ];
    }

    /**
     * @return array<string, PageRegistration>
     */
    protected static function sharedWithMePage(): array
    {
        if (! config('filament-library.sharing.shared_with_me', true)) {
            return [];
        }

        return [
            'shared-with-me' => Pages\SharedWithMe::route('/shared-with-me'),
        ];
    }

    /**
     * @return array<int, BulkManagePermissionsAction>
     */
    protected static function bulkManagePermissionsActions(): array
    {
        if (! config('filament-library.permissions.bulk_manage_action', false)) {
            return [];
        }

        return [
            BulkManagePermissionsAction::make(),
        ];
    }

    public static function getEditUrl($record): string
    {
        return match ($record->type) {
            'folder' => static::getUrl('edit-folder', ['record' => $record]),
            'file' => static::getUrl('edit-file', ['record' => $record]),
            'link' => static::getUrl('edit-link', ['record' => $record]),
            default => static::getUrl('edit-folder', ['record' => $record]),
        };
    }
}
