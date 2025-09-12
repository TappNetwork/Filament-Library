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
                    ->icon(fn (LibraryItem $record): string =>
                        $record->type === 'folder' ? 'heroicon-s-folder' : 'heroicon-o-document'
                    )
                    ->iconColor('gray')
                    ->iconPosition('before')
                    ->extraAttributes(['class' => 'library-item-name-column']),
                Tables\Columns\TextColumn::make('updater.name')
                    ->label('Modified By')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modified At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('size')
                    ->label('Size')
                    ->formatStateUsing(function (LibraryItem $record): string {
                        if ($record->type === 'folder') {
                            return '-';
                        }
                        $media = $record->getFirstMedia('files');
                        return $media ? static::formatFileSize($media->size) : '-';
                    })
                    ->sortable()
                    ->toggleable(),
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

    public static function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
