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
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Tapp\FilamentLibrary\Models\LibraryItem;
use Tapp\FilamentLibrary\Tables\Actions\BulkManagePermissionsAction;
use Tapp\FilamentLibrary\Tables\Columns\PermissionsColumn;

class LibraryItemResource extends Resource
{
    protected static ?string $model = LibraryItem::class;

    protected static ?int $deletedParentId = null;

    protected static ?string $slug = 'library';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-folder';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Resource Library';
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public static function getNavigationLabel(): string
    {
        return 'All Folders';
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
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                \Filament\Forms\Components\Select::make('type')
                    ->options([
                        'folder' => 'Folder',
                        'file' => 'File',
                        'link' => 'External Link',
                    ])
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('external_url', null)),

                // Folder form fields
                \Filament\Forms\Components\Textarea::make('link_description')
                    ->label('Description')
                    ->visible(fn (callable $get) => $get('type') === 'folder')
                    ->rows(3),

                // File form fields
                \Filament\Forms\Components\SpatieMediaLibraryFileUpload::make('files')
                    ->label('File')
                    ->collection('files')
                    ->visible(fn (callable $get) => $get('type') === 'file')
                    ->required(fn (callable $get) => $get('type') === 'file'),

                // Link form fields
                \Filament\Forms\Components\TextInput::make('external_url')
                    ->label('URL')
                    ->url()
                    ->visible(fn (callable $get) => $get('type') === 'link')
                    ->required(fn (callable $get) => $get('type') === 'link'),

                \Filament\Forms\Components\Textarea::make('link_description')
                    ->label('Description')
                    ->visible(fn (callable $get) => $get('type') === 'link')
                    ->rows(3),
            ]);
    }

    public static function folderForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('Folder Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Enter folder name'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->icon(fn (?LibraryItem $record): string => $record?->getDisplayIcon() ?? 'heroicon-o-document')
                    ->iconPosition('before')
                    ->url(function (?LibraryItem $record): ?string {
                        if (!$record) {
                            return null;
                        }

                        return match ($record->type) {
                            'folder' => static::getUrl('index', ['parent' => $record->id]),
                            'link' => static::getUrl('view', ['record' => $record]),
                            'file' => static::getUrl('view', ['record' => $record]),
                            default => null,
                        };
                    }),
                Tables\Columns\TextColumn::make('updater.name')
                    ->label('Modified By')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modified At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
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
                    ->tooltip(fn (?LibraryItem $record) => $record?->external_url),
                PermissionsColumn::make('permissions')
                    ->label('Permissions')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'folder' => 'Folder',
                        'file' => 'File',
                        'link' => 'External Link',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
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
                        ->url(function (LibraryItem $record): string {
                            return static::getEditUrl($record);
                        }),
                    DeleteAction::make()
                        ->color('gray')
                        ->before(function (LibraryItem $record) {
                            // Store parent_id before deletion
                            static::$deletedParentId = $record->parent_id;
                        })
                        ->successRedirectUrl(function () {
                            // Redirect to the parent folder after deletion
                            $parentId = static::$deletedParentId;

                            return static::getUrl('index', $parentId ? ['parent' => $parentId] : []);
                        }),
                    RestoreAction::make(),
                    ForceDeleteAction::make()
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
                    BulkManagePermissionsAction::make(),
                    DeleteBulkAction::make()
                        ->successRedirectUrl(function () {
                            // For bulk actions, redirect to current folder (maintain current location)
                            $currentParent = request()->get('parent');

                            return static::getUrl('index', $currentParent ? ['parent' => $currentParent] : []);
                        }),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make()
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLibraryItems::route('/'),
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
