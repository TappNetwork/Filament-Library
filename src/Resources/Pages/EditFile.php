<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class EditFile extends EditRecord
{
    protected static string $resource = LibraryItemResource::class;

    protected ?int $parentId = null;

    public function getTitle(): string
    {
        return "Edit File";
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Add "Up One Level" action if we have a parent
        if ($this->getRecord()->parent_id) {
            $actions[] = Action::make('up_one_level')
                ->label('Up One Level')
                ->icon('heroicon-o-arrow-up')
                ->color('gray')
                ->url(
                    fn (): string => static::getResource()::getUrl('index', ['parent' => $this->getRecord()->parent_id])
                );
        }

        // Add "View" action - for folders go to list page, for files/links go to view page
        $viewUrl = $this->getRecord()->type === 'folder'
            ? static::getResource()::getUrl('index', ['parent' => $this->getRecord()->id])
            : static::getResource()::getUrl('view', ['record' => $this->getRecord()->id]);

        $actions[] = Action::make('view')
            ->label('View')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->url($viewUrl);

        $actions[] = DeleteAction::make()
            ->before(function () {
                // Store parent_id before deletion
                $this->parentId = $this->getRecord()->parent_id;
            })
            ->successRedirectUrl(function () {
                // Redirect to the parent folder after deletion
                $parentId = $this->parentId;

                return static::getResource()::getUrl('index', $parentId ? ['parent' => $parentId] : []);
            });

        return $actions;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Remove fields that shouldn't be editable
        unset($data['type']);
        unset($data['parent_id']);

        // Load the current file into the media input
        $record = $this->getRecord();
        if ($record && $record->getFirstMedia('files')) {
            $data['files'] = [$record->getFirstMedia('files')->id];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Set the updated_by field
        $data['updated_by'] = auth()->user()?->id;

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
                    ->collection('files'),

                \Filament\Forms\Components\Textarea::make('link_description')
                    ->label('Description')
                    ->rows(3),

                \Filament\Forms\Components\Select::make('general_access')
                    ->label('General Access')
                    ->options(function () {
                        $options = \Tapp\FilamentLibrary\Models\LibraryItem::getGeneralAccessOptions();

                        // Remove inherit option if no parent folder
                        if (!$this->getRecord()->parent_id) {
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
                \Filament\Forms\Components\Select::make('created_by')
                    ->label('Creator')
                    ->options(function () {
                        return \App\Models\User::all()->mapWithKeys(function ($user) {
                            // Check if 'name' field exists and has a value
                            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'name') && $user->name) {
                                return [$user->id => $user->name . ' (' . $user->email . ')'];
                            }

                            // Fall back to first_name/last_name if available
                            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'first_name') && \Illuminate\Support\Facades\Schema::hasColumn('users', 'last_name')) {
                                $firstName = $user->first_name ?? '';
                                $lastName = $user->last_name ?? '';
                                $fullName = trim($firstName . ' ' . $lastName);

                                if ($fullName) {
                                    return [$user->id => $fullName . ' (' . $user->email . ')'];
                                }
                            }

                            // Fall back to email only
                            return [$user->id => $user->email];
                        });
                    })
                    ->searchable()
                    ->preload()
                    ->disabled(function () {
                        // Only allow changes if user has library admin access (Admin role)
                        return !\Tapp\FilamentLibrary\FilamentLibraryPlugin::isLibraryAdmin(auth()->user());
                    })
                    ->helperText('Creator receives owner permissions'),
            ]);
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            static::getResource()::getUrl() => 'Library',
        ];

        $record = $this->getRecord();

        if ($record->parent_id) {
            // Cache the breadcrumb path to avoid repeated computation
            $cacheKey = 'breadcrumbs_' . $record->parent_id;
            $path = cache()->remember($cacheKey, 300, function () use ($record) { // 5 minute cache
                $current = $record->parent;
                $path = [];

                while ($current) {
                    array_unshift($path, $current);
                    $current = $current->parent;
                }

                return $path;
            });

            // Generate URLs more efficiently
            $baseUrl = static::getResource()::getUrl('index');
            foreach ($path as $folder) {
                $breadcrumbs[$baseUrl . '?parent=' . $folder->id] = $folder->name;
            }
        }

        // Add current item to breadcrumbs
        $breadcrumbs[] = $record->name;

        return $breadcrumbs;
    }
}
