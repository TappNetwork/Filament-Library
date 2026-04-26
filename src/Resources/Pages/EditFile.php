<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Tapp\FilamentLibrary\Models\LibraryItem;
use Tapp\FilamentLibrary\Models\LibraryItemTag;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;
use Tapp\FilamentLibrary\Rules\UniqueTagName;

class EditFile extends EditLibraryItemPage
{
    protected static string $resource = LibraryItemResource::class;

    protected ?int $parentId = null;

    public function getTitle(): string
    {
        return 'Edit File';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);

        // Load the current file into the media input
        $record = $this->getRecord();
        if ($record && $record->getFirstMedia('files')) {
            $data['files'] = [$record->getFirstMedia('files')->id];
        }

        return $data;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                SpatieMediaLibraryFileUpload::make('files')
                    ->label('File')
                    ->maxSize(512000), // 500MB

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
                        $tag = LibraryItemTag::create([
                            'name' => $data['name'],
                            'slug' => Str::slug($data['name']),
                        ]);

                        return $tag->id;
                    }),

                Select::make('general_access')
                    ->label('General Access')
                    ->options(function () {
                        $options = LibraryItem::getGeneralAccessOptions();

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

                        $baseText = 'Set the baseline access level for this file. User-level permissions can override this setting.';

                        if ($inherited) {
                            return $baseText . "\n\nCurrently inheriting: {$inherited}";
                        }

                        return $baseText;
                    })
                    ->visible(fn () => $this->getRecord()->hasPermission(auth()->user(), 'share')),

                // Creator select field
                $this->getCreatorSelectField(),
            ]);
    }
}
