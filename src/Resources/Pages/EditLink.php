<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Tapp\FilamentLibrary\FilamentLibraryPlugin;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;
use Tapp\FilamentLibrary\Rules\UniqueTagName;

class EditLink extends EditLibraryItemPage
{
    protected static string $resource = LibraryItemResource::class;

    protected ?int $parentId = null;

    public function getTitle(): string
    {
        return 'Edit External Link';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('external_url')
                    ->label('URL')
                    ->url()
                    ->required(),

                Textarea::make('link_description')
                    ->label('Description')
                    ->rows(3),

                Select::make('tags')
                    ->label('Tags')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->rules([new UniqueTagName])
                            ->validationAttribute('tag name'),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        $tag = FilamentLibraryPlugin::libraryItemTagModelClass()::create([
                            'name' => $data['name'],
                            'slug' => Str::slug($data['name']),
                        ]);

                        return $tag->id;
                    }),

                Select::make('general_access')
                    ->label('General Access')
                    ->options(function () {
                        $options = FilamentLibraryPlugin::libraryItemModelClass()::getGeneralAccessOptions();

                        // Remove inherit option if no parent folder
                        if (! $this->getRecord()->parent_id) {
                            unset($options['inherit']);
                        }

                        return $options;
                    })
                    ->default(function () {
                        // Default to inherit if has parent, otherwise private
                        return $this->getRecord()->parent_id ? 'inherit' : 'private';
                    })
                    ->helperText(function () {
                        $record = $this->getRecord();
                        $inherited = $record->getInheritedGeneralAccessDisplay();

                        $baseText = 'Only library admins can change general access (e.g. make an item visible to everyone). Share with specific users via User Permissions instead.';

                        if ($inherited) {
                            return $baseText . "\n\nCurrently inheriting: {$inherited}";
                        }

                        return $baseText;
                    })
                    ->visible(fn () => FilamentLibraryPlugin::isLibraryAdmin(auth()->user())),

                // Creator select field
                $this->getCreatorSelectField(),
            ]);
    }
}
