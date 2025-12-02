<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Tapp\FilamentLibrary\Resources\LibraryItemResource;

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

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                \Filament\Forms\Components\SpatieMediaLibraryFileUpload::make('files')
                    ->label('File')
                    ->maxSize(512000), // 500MB

                \Filament\Forms\Components\Textarea::make('link_description')
                    ->label('Description')
                    ->rows(3),

                \Filament\Forms\Components\Select::make('tags')
                    ->label('Tags')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->rules([new \Tapp\FilamentLibrary\Rules\UniqueTagName])
                            ->validationAttribute('tag name'),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        $slug = \Illuminate\Support\Str::slug($data['name']);

                        // Check if a tag with this slug already exists
                        $existingTag = \Tapp\FilamentLibrary\Models\LibraryItemTag::where('slug', $slug)->first();

                        if ($existingTag) {
                            // Re-validate to trigger form validation display
                            \Illuminate\Support\Facades\Validator::make($data, [
                                'name' => [
                                    function ($attribute, $value, $fail) use ($existingTag) {
                                        if ($existingTag) {
                                            $fail('A tag with this name already exists.');
                                        }
                                    },
                                ],
                            ])->validate();
                        }

                        $tag = \Tapp\FilamentLibrary\Models\LibraryItemTag::create([
                            'name' => $data['name'],
                            'slug' => $slug,
                        ]);

                        return $tag->id;
                    }),

                \Filament\Forms\Components\Select::make('general_access')
                    ->label('General Access')
                    ->options(function () {
                        $options = \Tapp\FilamentLibrary\Models\LibraryItem::getGeneralAccessOptions();

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
