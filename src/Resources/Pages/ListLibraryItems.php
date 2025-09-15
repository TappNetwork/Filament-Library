<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;
use Tapp\FilamentLibrary\Models\LibraryItem;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;

class ListLibraryItems extends ListRecords
{
    protected static string $resource = LibraryItemResource::class;

    public ?int $parentId = null;
    public ?LibraryItem $parentFolder = null;

    public function mount(): void
    {
        parent::mount();

        $this->parentId = request()->get('parent');

        if ($this->parentId) {
            $this->parentFolder = LibraryItem::find($this->parentId);
        }
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Add "Up One Level" action if we're in a subfolder
        if ($this->parentId && $this->parentFolder) {
            $actions[] = Action::make('up_one_level')
                ->label('Up One Level')
                ->icon('heroicon-o-arrow-up')
                ->url(fn (): string =>
                    $this->parentFolder->parent_id
                        ? static::getResource()::getUrl('index', ['parent' => $this->parentFolder->parent_id])
                        : static::getResource()::getUrl('index')
                )
                ->color('gray');
        }

        // Add "+ New" dropdown action group
        $actions[] = ActionGroup::make([
            Action::make('create_folder')
                ->label('Create Folder')
                ->icon('heroicon-o-folder-plus')
                ->form([
                    TextInput::make('name')
                        ->label('Folder Name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Enter folder name'),
                ])
                ->action(function (array $data): void {
                    LibraryItem::create([
                        'name' => $data['name'],
                        'type' => 'folder',
                        'parent_id' => $this->parentId,
                        'created_by' => auth()->user()?->id,
                        'updated_by' => auth()->user()?->id,
                    ]);

                    $this->redirect(static::getResource()::getUrl('index', $this->parentId ? ['parent' => $this->parentId] : []));
                }),
            Action::make('upload_file')
                ->label('Upload File')
                ->icon('heroicon-o-document-plus')
                ->form([
                    FileUpload::make('file')
                        ->label('Upload File')
                        ->required()
                        ->maxSize(10240) // 10MB
                        ->disk('public')
                        ->directory('library-files')
                        ->visibility('private'),
                ])
                ->action(function (array $data): void {
                    $filePath = $data['file'];

                    // Extract filename from the path
                    $fileName = basename($filePath);

                    LibraryItem::create([
                        'name' => $fileName,
                        'type' => 'file',
                        'parent_id' => $this->parentId,
                        'created_by' => auth()->user()?->id,
                        'updated_by' => auth()->user()?->id,
                    ]);

                    $this->redirect(static::getResource()::getUrl('index', $this->parentId ? ['parent' => $this->parentId] : []));
                }),
        ])
            ->label('New')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->button();

        return $actions;
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getTableQuery();

        if ($this->parentId) {
            $query->where('parent_id', $this->parentId);
        } else {
            $query->whereNull('parent_id');
        }

        return $query;
    }

    public function getTitle(): string
    {
        if ($this->parentFolder) {
            return $this->parentFolder->name;
        }

        return 'All Folders';
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            static::getResource()::getUrl() => 'All Folders',
        ];

        if ($this->parentFolder) {
            $current = $this->parentFolder;
            $path = [];

            while ($current) {
                array_unshift($path, $current);
                $current = $current->parent;
            }

            foreach ($path as $folder) {
                $breadcrumbs[static::getResource()::getUrl('index', ['parent' => $folder->id])] = $folder->name;
            }
        }

        return $breadcrumbs;
    }
}
