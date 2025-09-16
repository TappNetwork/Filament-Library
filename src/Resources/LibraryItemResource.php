<?php

namespace Tapp\FilamentLibrary\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Tapp\FilamentLibrary\Models\LibraryItem;

class LibraryItemResource extends Resource
{
    protected static ?string $model = LibraryItem::class;

    protected static ?string $slug = 'library';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static string|\UnitEnum|null $navigationGroup = 'Resource Library';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'All Folders';

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
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function folderForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('Folder Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Enter folder name'),
            ]);
    }

    public static function fileForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\FileUpload::make('file')
                    ->label('Upload File')
                    ->required()
                    ->maxSize(10240) // 10MB
                    ->disk('public')
                    ->directory('library-files')
                    ->visibility('private'),
            ]);
    }

    public static function editForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->icon(fn (LibraryItem $record): string =>
                        $record->type === 'folder' ? 'heroicon-s-folder' : 'heroicon-o-document'
                    )
                    ->iconColor('gray')
                    ->iconPosition('before')
                    ->extraAttributes(['class' => 'library-item-name-column']),
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'folder' => 'Folder',
                        'file' => 'File',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
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
                    ->color('gray'),
                    DeleteAction::make()
                    ->color('gray')
                        ->successRedirectUrl(function (LibraryItem $record) {
                            // Redirect to the parent folder after deletion
                            $parentId = $record->parent_id;
                            return static::getUrl('index', $parentId ? ['parent' => $parentId] : []);
                        }),
                    RestoreAction::make(),
                    ForceDeleteAction::make()
                        ->successRedirectUrl(function (LibraryItem $record) {
                            // Redirect to the parent folder after deletion
                            $parentId = $record->parent_id;
                            return static::getUrl('index', $parentId ? ['parent' => $parentId] : []);
                        }),
                ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->iconButton(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
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
            'create' => Pages\CreateLibraryItem::route('/create'),
            'create-folder' => Pages\CreateFolder::route('/create-folder'),
            'create-file' => Pages\CreateFile::route('/create-file'),
            'view' => Pages\ViewLibraryItem::route('/{record}'),
            'edit' => Pages\EditLibraryItem::route('/{record}/edit'),
        ];
    }

}
