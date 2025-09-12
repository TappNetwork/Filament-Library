<?php

namespace Tapp\FilamentLibrary\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'folder' => 'success',
                        'file' => 'info',
                    }),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent Folder')
                    ->searchable()
                    ->sortable(),
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
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'edit' => Pages\EditLibraryItem::route('/{record}/edit'),
        ];
    }
}
