<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class EditLibraryItem extends EditRecord
{
    protected static string $resource = LibraryItemResource::class;

    protected ?int $parentId = null;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Automatically redirect to the correct edit page based on type
        $record = $this->getRecord();
        $editUrl = static::getResource()::getEditUrl($record);

        $this->redirect($editUrl);
    }

    public function getTitle(): string
    {
        $record = $this->getRecord();
        $type = match ($record->type) {
            'folder' => 'Folder',
            'file' => 'File',
            'link' => 'External Link',
            default => 'Item',
        };

        return "Edit {$type}";
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Add "View Folder" action if we have a parent
        if ($this->getRecord()->parent_id) {
            $actions[] = Action::make('view_folder')
                ->label('View Folder')
                ->icon('heroicon-o-arrow-up')
                ->color('gray')
                ->url(
                    fn (): string => static::getResource()::getUrl('index', ['parent' => $this->getRecord()->parent_id])
                );
        }

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
        unset($data['created_by']);

        return $data;
    }


    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Set the updated_by field
        $data['updated_by'] = auth()->user()?->id;

        return $data;
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(static::getResource()::form(
                \Filament\Schemas\Schema::make()
            ))
                ->statePath('data')
                ->model($this->getRecord())
                ->schema([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    // Folder form fields
                    \Filament\Forms\Components\Textarea::make('link_description')
                        ->label('Description')
                        ->visible(fn () => $this->getRecord()->type === 'folder')
                        ->rows(3),

                    // File form fields
                    \Filament\Forms\Components\SpatieMediaLibraryFileUpload::make('files')
                        ->label('File')
                        ->collection('files')
                        ->visible(fn () => $this->getRecord()->type === 'file'),

                    // Link form fields
                    \Filament\Forms\Components\TextInput::make('external_url')
                        ->label('URL')
                        ->url()
                        ->visible(fn () => $this->getRecord()->type === 'link')
                        ->required(fn () => $this->getRecord()->type === 'link'),

                    \Filament\Forms\Components\Textarea::make('link_description')
                        ->label('Description')
                        ->visible(fn () => $this->getRecord()->type === 'link')
                        ->rows(3),

                    // Ownership information section
                    \Filament\Forms\Components\Section::make('Ownership')
                        ->description('The Creator is permanent and cannot be changed. The Owner manages sharing permissions and can be transferred to another user.')
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('creator_info')
                                ->label('Created by')
                                ->content(function () {
                                    $creator = $this->getRecord()->creator;
                                    if (!$creator) {
                                        return 'Unknown';
                                    }

                                    // Check if 'name' field exists and has a value
                                    if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'name') && $creator->name) {
                                        return $creator->name . ' (' . $creator->email . ')';
                                    }

                                    // Fall back to first_name/last_name if available
                                    if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'first_name') && \Illuminate\Support\Facades\Schema::hasColumn('users', 'last_name')) {
                                        $firstName = $creator->first_name ?? '';
                                        $lastName = $creator->last_name ?? '';
                                        $fullName = trim($firstName . ' ' . $lastName);

                                        if ($fullName) {
                                            return $fullName . ' (' . $creator->email . ')';
                                        }
                                    }

                                    // Fall back to email only
                                    return $creator->email;
                                }),

                            \Filament\Forms\Components\Placeholder::make('owner_info')
                                ->label('Current Owner')
                                ->content(function () {
                                    $owner = $this->getRecord()->getCurrentOwner();
                                    if (!$owner) {
                                        return 'No owner assigned';
                                    }

                                    // Check if 'name' field exists and has a value
                                    if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'name') && $owner->name) {
                                        return $owner->name . ' (' . $owner->email . ')';
                                    }

                                    // Fall back to first_name/last_name if available
                                    if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'first_name') && \Illuminate\Support\Facades\Schema::hasColumn('users', 'last_name')) {
                                        $firstName = $owner->first_name ?? '';
                                        $lastName = $owner->last_name ?? '';
                                        $fullName = trim($firstName . ' ' . $lastName);

                                        if ($fullName) {
                                            return $fullName . ' (' . $owner->email . ')';
                                        }
                                    }

                                    // Fall back to email only
                                    return $owner->email;
                                }),

                            \Filament\Forms\Components\Select::make('owner_id')
                                ->label('Transfer Ownership To')
                                ->options(function () {
                                    // Get all users who have access to this item
                                    $users = \App\Models\User::whereHas('roles', function ($query) {
                                        $query->whereIn('name', [
                                            'Admin',
                                            'Board of Directors',
                                            'Community Coach',
                                            'COE Reviewer',
                                            'Community Admin',
                                            'Community Member'
                                        ]);
                                    })->get();

                                    return $users->pluck('email', 'id')->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->default(function () {
                                    // Default to current owner
                                    $currentOwner = $this->getRecord()->getCurrentOwner();
                                    return $currentOwner ? $currentOwner->id : null;
                                })
                                ->visible(function () {
                                    // Only show to current owner (not just creator)
                                    $record = $this->getRecord();
                                    $currentUser = auth()->user();
                                    $effectiveRole = $record->getEffectiveRole($currentUser);
                                    return $effectiveRole === 'owner';
                                })
                                ->helperText('Transfer ownership to another user. You will remain the creator (permanent) but lose owner privileges (managing sharing). The new owner will be able to manage permissions via the User Permissions section below.')
                                ->afterStateUpdated(function ($state) {
                                    if ($state && $state !== $this->getRecord()->getCurrentOwner()?->id) {
                                        $newOwner = \App\Models\User::find($state);
                                        if ($newOwner) {
                                            $this->getRecord()->transferOwnership($newOwner);
                                            $this->dispatch('notify', [
                                                'type' => 'success',
                                                'message' => 'Ownership transferred successfully!'
                                            ]);
                                        }
                                    }
                                }),
                        ])
                        ->collapsible()
                        ->collapsed(false),
                ]),
        ];
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
