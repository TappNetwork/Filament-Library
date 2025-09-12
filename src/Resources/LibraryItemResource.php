<?php

namespace Tapp\FilamentLibrary\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\Select::make('type')
                    ->options([
                        'folder' => 'Folder',
                        'file' => 'File',
                    ])
                    ->required(),
                \Filament\Forms\Components\Select::make('parent_id')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload(),
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
                    ->formatStateUsing(function (LibraryItem $record): string {
                        $iconSvg = $record->type === 'folder' 
                            ? '<svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>'
                            : '<svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
                        return "<div class='flex items-center gap-2'>{$iconSvg}<span>{$record->name}</span></div>";
                    })
                    ->html(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(fn (LibraryItem $record): string =>
                $record->type === 'folder'
                    ? static::getUrl('index', ['parent' => $record->id])
                    : static::getUrl('view', ['record' => $record])
            );
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
