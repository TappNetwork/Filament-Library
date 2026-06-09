@php
    use Tapp\FilamentLibrary\Support\LibraryFilePreviewResolver;

    $media = $record->getFirstMedia('files') ?? $record->getFirstMedia();
    $fileUrl = $record->getSecureUrl();
    $preview = $media ? LibraryFilePreviewResolver::resolve($media) : null;
@endphp

@if($media && $fileUrl && $preview?->isPreviewable())
    <div class="w-full">
        @include('filament-library::infolists.components.previews.'.$preview->type->viewName(), [
            'media' => $media,
            'fileUrl' => $fileUrl,
            'preview' => $preview,
        ])
    </div>
@elseif($media && $fileUrl)
    <div class="w-full">
        @include('filament-library::infolists.components.previews.download', [
            'fileUrl' => $fileUrl,
            'message' => $preview?->fallbackMessage ?? 'This file type cannot be previewed. Please download to view.',
        ])
    </div>
@else
    <x-filament::section>
        <div class="filament-library-unpreviewable">
            <div class="filament-library-unpreviewable-message">
                No file is currently associated with this item.
            </div>
        </div>
    </x-filament::section>
@endif
