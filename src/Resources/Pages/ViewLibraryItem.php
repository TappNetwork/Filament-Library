<?php

namespace Tapp\FilamentLibrary\Resources\Pages;

use Filament\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Tapp\FilamentLibrary\Infolists\Components\VideoEmbed;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class ViewLibraryItem extends ViewRecord
{
    protected static string $resource = LibraryItemResource::class;

    protected ?int $parentId = null;

    public function getTitle(): string
    {
        $record = $this->getRecord();

        // For files and links, show the item name
        if ($record->type === 'link' || $record->type === 'file') {
            return $record->name;
        }

        $type = match ($record->type) {
            'folder' => 'Folder',
            'file' => 'File',
            'link' => 'External Link',
            default => 'Item',
        };

        return "View {$type}";
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

        // Add "Visit Link" action for external links
        if ($this->getRecord()->type === 'link' && $this->getRecord()->external_url) {
            $actions[] = Action::make('visit_link')
                ->label('Visit Link')
                ->color('gray')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => $this->getRecord()->external_url)
                ->openUrlInNewTab();
        }

        // Add "Download" action for files
        if ($this->getRecord()->type === 'file' && $this->getRecord()->getFirstMedia('files')) {
            $actions[] = Action::make('download_file')
                ->label('Download')
                ->color('gray')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn () => $this->getRecord()->getFirstMedia('files')->getUrl())
                ->openUrlInNewTab();
        }

        $actions[] = \Filament\Actions\EditAction::make()
            ->url(fn () => static::getResource()::getEditUrl($this->getRecord()));
        $actions[] = \Filament\Actions\DeleteAction::make()
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

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            static::getResource()::getUrl() => 'All Folders',
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

    public function infolist(Schema $schema): Schema
    {
        $record = $this->getRecord();

        return $schema
            ->components([
                // Video for external links that are videos (no card wrapper)
                VideoEmbed::make('external_url')
                    ->hiddenLabel()
                    ->visible(fn () => $record->type === 'link' && $record->isVideoUrl())
                    ->columnSpanFull(),

                // File preview for files
                Section::make()
                    ->schema([
                        \Filament\Infolists\Components\ViewEntry::make('file_preview')
                            ->view('filament-library::infolists.components.file-preview')
                            ->viewData(fn () => ['record' => $record])
                    ])
                    ->visible(fn () => $record->type === 'file' && $record->getFirstMedia('files'))
                    ->columnSpanFull(),

                // Item details section
                Section::make()
                    ->schema([
                        // Row 1: Name, Type
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Name'),
                                TextEntry::make('type')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => match ($state) {
                                        'folder' => 'Folder',
                                        'file' => 'File',
                                        'link' => 'External Link',
                                        default => $state,
                                    }),
                            ]),

                        // Row 2: Created At, Created By
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime(),
                                TextEntry::make('creator.name')
                                    ->label('Created By'),
                            ]),

                        // Row 3: Description (full width)
                        TextEntry::make('link_description')
                            ->label('Description')
                            ->visible(fn () => $record->link_description)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                // Media section for files
                Section::make('Media')
                    ->headerActions([
                        Action::make('downloadAll')
                            ->label('Download All Files')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->visible(fn () => $record->type === 'file' && $record->getMedia()->count() > 0)
                            ->action(function () {
                                // TODO: Implement download all functionality
                            }),
                    ])
                    ->schema([
                        RepeatableEntry::make('media')
                            ->label('')
                            ->schema([
                                Flex::make([
                                    ImageEntry::make('preview')
                                        ->state(fn ($media) => $media->getUrl('thumb'))
                                        ->width(300)
                                        ->height(300)
                                        ->visible(fn ($media) => $media->hasGeneratedConversion('thumb'))
                                        ->label(''),
                                    Grid::make(1)
                                        ->schema([
                                            TextEntry::make('name')
                                                ->label('File Name'),
                                            TextEntry::make('size')
                                                ->label('File Size')
                                                ->formatStateUsing(fn ($state) => number_format($state / 1024 / 1024, 2) . ' MB'),
                                        ]),
                                ])
                                    ->from('lg'),
                            ])
                            ->visible(fn () => $record->type === 'file' && $record->getMedia()->count() > 0),
                    ])
                    ->visible(fn () => $record->type === 'file' && $record->getMedia()->count() > 0),
            ]);
    }
}
