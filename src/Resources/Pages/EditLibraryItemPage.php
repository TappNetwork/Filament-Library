<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Schema;

abstract class EditLibraryItemPage extends EditRecord
{
    protected function getHeaderActions(): array
    {
        $actions = [];

        // Add "Up One Level" action if we have a parent
        if ($this->getRecord()->parent_id) {
            $actions[] = Action::make('up_one_level')
                ->label('Up One Level')
                ->icon('heroicon-o-arrow-up')
                ->color('gray')
                ->url(function () {
                    $parent = $this->getRecord()->parent;
                    if ($parent) {
                        return static::getResource()::getUrl('index', ['parent' => $parent->id]);
                    }

                    return static::getResource()::getUrl('index');
                });
        }

        // Add "View" action - for folders go to list page, for files/links go to view page
        $actions[] = Action::make('view')
            ->label('View')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->url(function () {
                $record = $this->getRecord();
                if ($record->type === 'folder') {
                    return static::getResource()::getUrl('index', ['parent' => $record->id]);
                }

                return static::getResource()::getUrl('view', ['record' => $record->id]);
            });

        // Add "Delete" action
        $actions[] = DeleteAction::make()
            ->color('gray')
            ->before(function () {
                // Store parent_id before deletion
                $this->parentId = $this->getRecord()->parent_id;
            })
            ->after(function () {
                // Redirect to the parent folder after deletion
                if ($this->parentId) {
                    $this->redirect(static::getResource()::getUrl('index', ['parent' => $this->parentId]));
                } else {
                    $this->redirect(static::getResource()::getUrl('index'));
                }
            });

        return $actions;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Remove fields that shouldn't be editable
        unset($data['type']);
        unset($data['parent_id']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Set the updated_by field
        $data['updated_by'] = auth()->user()?->id;

        return $data;
    }

    protected function getCreatorSelectField(): Select
    {
        return Select::make('created_by')
            ->label('Creator')
            ->searchable()
            ->getSearchResultsUsing(function (string $search) {
                $query = \App\Models\User::query();

                // Check if 'name' field exists and has a value
                if (Schema::hasColumn('users', 'name')) {
                    $query->where('name', 'like', "%{$search}%");
                } else {
                    // Fall back to first_name/last_name if available
                    if (Schema::hasColumn('users', 'first_name') && Schema::hasColumn('users', 'last_name')) {
                        $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                    } else {
                        // Fall back to email only
                        $query->where('email', 'like', "%{$search}%");
                    }
                }

                return $query->limit(50)->get()->mapWithKeys(function ($user) {
                    $displayName = $this->getUserDisplayName($user);

                    return [$user->id => $displayName];
                });
            })
            ->getOptionLabelUsing(function ($value) {
                $user = \App\Models\User::find($value);

                return $user ? $this->getUserDisplayName($user) : '';
            })
            ->disabled(function () {
                $user = auth()->user();
                $record = $this->getRecord();

                // Allow changes if user is library admin OR if user is the creator
                return ! \Tapp\FilamentLibrary\FilamentLibraryPlugin::isLibraryAdmin($user) &&
                       $record->created_by !== $user->id;
            })
            ->helperText('Creator receives owner permissions');
    }

    protected function getUserDisplayName($user): string
    {
        // Check if 'name' field exists and has a value
        if (Schema::hasColumn('users', 'name') && $user->name) {
            return $user->name;
        }

        // Fall back to first_name/last_name if available
        if (Schema::hasColumn('users', 'first_name') && Schema::hasColumn('users', 'last_name')) {
            $firstName = $user->first_name ?? '';
            $lastName = $user->last_name ?? '';
            $fullName = trim($firstName . ' ' . $lastName);
            if ($fullName) {
                return $fullName . ' (' . $user->email . ')';
            }
        }

        // Fall back to email only
        return $user->email;
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            static::getResource()::getUrl() => 'Library',
        ];

        // Cache the breadcrumb path to avoid repeated computation
        static $breadcrumbCache = [];
        $recordId = $this->getRecord()->id;

        if (! isset($breadcrumbCache[$recordId])) {
            $path = [];
            $current = $this->getRecord();

            // Generate URLs more efficiently
            while ($current && $current->parent_id) {
                $path[] = [
                    'name' => $current->name,
                    'url' => static::getResource()::getUrl('index', ['parent' => $current->id]),
                ];
                $current = $current->parent;
            }

            $breadcrumbCache[$recordId] = array_reverse($path);
        }

        foreach ($breadcrumbCache[$recordId] as $item) {
            $breadcrumbs[$item['url']] = $item['name'];
        }

        // Add current item to breadcrumbs
        $breadcrumbs[''] = $this->getRecord()->name;

        return $breadcrumbs;
    }
}
