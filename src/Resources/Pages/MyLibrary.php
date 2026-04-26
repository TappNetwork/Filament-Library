<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Tapp\FilamentLibrary\Models\LibraryItem;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class MyLibrary extends ListRecords
{
    protected static string $resource = LibraryItemResource::class;

    protected static ?string $title = 'My Library';

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        // Show only the current user's personal folder and its contents
        $user = auth()->user();
        if ($user) {
            $personalFolder = LibraryItem::getPersonalFolder($user);

            if ($personalFolder) {
                $query->where('parent_id', $personalFolder->id);
            } else {
                // If no personal folder exists, show empty result
                $query->whereRaw('1 = 0');
            }
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public function getTitle(): string
    {
        return 'My Documents';
    }

    public function getSubheading(): ?string
    {
        return 'Your personal files and folders';
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl() => 'Library',
            '' => 'My Documents',
        ];
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Add "+ New" dropdown action group for personal library
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
                    $user = auth()->user();
                    $personalFolder = LibraryItem::ensurePersonalFolder($user);

                    LibraryItem::create([
                        'name' => $data['name'],
                        'type' => 'folder',
                        'parent_id' => $personalFolder->id,
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                        'general_access' => 'private',
                    ]);

                    $this->redirect(static::getResource()::getUrl('my-documents'));
                }),
            Action::make('upload_file')
                ->label('Upload File')
                ->icon('heroicon-o-document-plus')
                ->schema([
                    FileUpload::make('file')
                        ->label('Upload File')
                        ->required()
                        ->maxSize(512000) // 500MB
                        ->disk('public')
                        ->directory('library-files')
                        ->visibility('private')
                        ->preserveFilenames(),
                ])
                ->action(function (array $data): void {
                    $user = auth()->user();
                    $personalFolder = LibraryItem::ensurePersonalFolder($user);
                    $filePath = $data['file'];
                    $fileName = basename($filePath);

                    $libraryItem = LibraryItem::create([
                        'name' => $fileName,
                        'type' => 'file',
                        'parent_id' => $personalFolder->id,
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                        'general_access' => 'private',
                    ]);

                    $libraryItem->addMediaFromDisk($filePath, 'public')
                        ->usingName($fileName)
                        ->usingFileName($fileName)
                        ->toMediaCollection();

                    $this->redirect(static::getResource()::getUrl('my-documents'));
                }),
            Action::make('create_link')
                ->label('Add External Link')
                ->icon('heroicon-o-link')
                ->schema([
                    TextInput::make('name')
                        ->label('Link Name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Enter link name'),
                    TextInput::make('url')
                        ->label('URL')
                        ->required()
                        ->url()
                        ->placeholder('https://example.com'),
                ])
                ->action(function (array $data): void {
                    $user = auth()->user();
                    $personalFolder = LibraryItem::ensurePersonalFolder($user);

                    LibraryItem::create([
                        'name' => $data['name'],
                        'type' => 'link',
                        'url' => $data['url'],
                        'parent_id' => $personalFolder->id,
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                        'general_access' => 'private',
                    ]);

                    $this->redirect(static::getResource()::getUrl('my-documents'));
                }),
        ])
            ->label('New')
            ->icon('heroicon-o-plus')
            ->button();

        return $actions;
    }
}
