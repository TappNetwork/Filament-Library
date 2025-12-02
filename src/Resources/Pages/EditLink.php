<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class EditLink extends EditLibraryItemPage
{
    protected static string $resource = LibraryItemResource::class;

    protected ?int $parentId = null;

    public function getTitle(): string
    {
        return 'Edit External Link';
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                \Filament\Forms\Components\TextInput::make('external_url')
                    ->label('URL')
                    ->url()
                    ->required(),

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
                            ->rules([
                                function ($attribute, $value, $fail) {
                                    if (empty($value)) {
                                        return;
                                    }
                                    
                                    $slug = \Illuminate\Support\Str::slug($value);
                                    $existingTag = \Tapp\FilamentLibrary\Models\LibraryItemTag::where('slug', $slug)->first();
                                    
                                    if ($existingTag) {
                                        $fail('A tag with this name already exists.');
                                    }
                                },
                            ])
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
                                    function ($attribute, $value, $fail) use ($slug, $existingTag) {
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

                        $baseText = 'Set the baseline access level for this link. User-level permissions can override this setting.';

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
