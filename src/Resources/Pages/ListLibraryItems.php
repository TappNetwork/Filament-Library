<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Tapp\FilamentLibrary\Models\LibraryItem;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

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
                ->url(
                    fn (): string => $this->parentFolder->parent_id
                        ? static::getResource()::getUrl('index', ['parent' => $this->parentFolder->parent_id])
                        : static::getResource()::getUrl('index')
                )
                ->color('gray');

            // Add "Edit" action for the current folder
            $actions[] = Action::make('edit_folder')
                ->label('Edit')
                ->icon('heroicon-o-pencil')
                ->color('gray')
                ->url(
                    fn (): string => static::getResource()::getUrl('edit', ['record' => $this->parentFolder])
                );
        }

        // Add "+ New" dropdown action group
        $actions[] = ActionGroup::make([
            Action::make('create_folder')
                ->label('Create Folder')
                ->icon('heroicon-o-folder-plus')
                ->schema([
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
                ->schema([
                    FileUpload::make('file')
                        ->label('Upload File')
                        ->required()
                        ->maxSize(10240) // 10MB
                        ->disk('public')
                        ->directory('library-files')
                        ->visibility('private')
                        ->preserveFilenames(), // This should preserve original filenames
                ])
                ->action(function (array $data): void {
                    $filePath = $data['file'];

                    // Extract filename from the stored path - this should preserve the original name
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
            Action::make('create_link')
                ->label('Add Link')
                ->icon('heroicon-o-link')
                ->schema([
                    TextInput::make('name')
                        ->label('Link Name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Enter link name'),
                    TextInput::make('external_url')
                        ->label('URL')
                        ->required()
                        ->url()
                        ->placeholder('https://example.com'),
                    TextInput::make('link_icon')
                        ->label('Icon (Heroicon name)')
                        ->placeholder('heroicon-o-link'),
                    Textarea::make('link_description')
                        ->label('Description')
                        ->rows(3)
                        ->placeholder('Optional description'),
                ])
                ->action(function (array $data): void {
                    LibraryItem::create([
                        'name' => $data['name'],
                        'type' => 'link',
                        'external_url' => $data['external_url'],
                        'link_icon' => $data['link_icon'] ?? 'heroicon-o-link',
                        'link_description' => $data['link_description'] ?? null,
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

    public function getSubheading(): ?string
    {
        if ($this->parentFolder && $this->parentFolder->link_description) {
            return $this->parentFolder->link_description;
        }

        return null;
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            static::getResource()::getUrl() => 'All Folders',
        ];

        if ($this->parentFolder) {
            // Cache the breadcrumb path to avoid repeated computation
            $cacheKey = 'breadcrumbs_' . $this->parentFolder->id;
            $path = cache()->remember($cacheKey, 300, function () { // 5 minute cache
                $current = $this->parentFolder;
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

        return $breadcrumbs;
    }

}
