@php
    $media = $record->getFirstMedia('files');
    $mimeType = $media?->mime_type;

    // Try temporary URL first, fallback to regular URL
    $fileUrl = null;
    if ($media) {
        try {
            $fileUrl = $media->getTemporaryUrl(now()->addMinutes(60));
        } catch (\Exception $e) {
            // Fallback to regular URL if temporary URLs not supported
            $fileUrl = $media->getUrl();

            // Ensure HTTPS for security
            if (str_starts_with($fileUrl, 'http://')) {
                $fileUrl = str_replace('http://', 'https://', $fileUrl);
            }
        }
    }

    // Get file extension for better type detection
    $extension = strtolower(pathinfo($media?->name, PATHINFO_EXTENSION));
@endphp

@php
    // Define previewable file types
    $previewableImages = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
    $previewableDocuments = ['pdf'];
    $previewableVideos = ['mp4', 'webm', 'ogg', 'avi', 'mov'];
    $previewableAudio = ['mp3', 'wav', 'ogg', 'm4a', 'aac'];

    // Check if file can be previewed
    $canPreview = false;
    $previewType = null;

    if (str_starts_with($mimeType, 'image/') || in_array($extension, $previewableImages)) {
        $canPreview = true;
        $previewType = 'image';
    } elseif (in_array($extension, $previewableDocuments) || $mimeType === 'application/pdf') {
        $canPreview = true;
        $previewType = 'pdf';
    } elseif (str_starts_with($mimeType, 'video/') || in_array($extension, $previewableVideos)) {
        $canPreview = true;
        $previewType = 'video';
    } elseif (str_starts_with($mimeType, 'audio/') || in_array($extension, $previewableAudio)) {
        $canPreview = true;
        $previewType = 'audio';
    }
@endphp

@if($media)
    <div class="w-full">
        @if($canPreview)
            @if($previewType === 'image')
                {{-- Image preview --}}
                <img src="{{ $fileUrl }}" alt="{{ $media->name }}" class="max-w-full h-auto rounded-lg shadow-lg">
            @elseif($previewType === 'pdf')
                {{-- PDF preview --}}
                <iframe src="{{ $fileUrl }}#toolbar=0&navpanes=0&scrollbar=1&view=FitH" title="{{ $media->name }}" class="pdf-container"></iframe>
            @elseif($previewType === 'video')
                {{-- Video preview --}}
                <div class="video-container">
                    <video controls class="w-full h-full">
                        <source src="{{ $fileUrl }}" type="{{ $mimeType }}">
                        Your browser does not support the video tag.
                    </video>
                </div>
            @elseif($previewType === 'audio')
                {{-- Audio preview --}}
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="mb-2 text-sm text-gray-500">Audio Preview:</div>
                    <audio controls class="w-full">
                        <source src="{{ $fileUrl }}" type="{{ $mimeType }}">
                        Your browser does not support the audio tag.
                    </audio>
                </div>
            @endif
        @else
            {{-- File cannot be previewed --}}
            <x-filament::section>
                <div class="filament-library-unpreviewable">
                    <div class="filament-library-unpreviewable-message">
                        This file type cannot be previewed. Please download to view.
                    </div>

                    <div class="filament-library-unpreviewable-button">
                        <x-filament::button
                            tag="a"
                            href="{{ $fileUrl }}"
                            target="_blank"
                            icon="heroicon-o-arrow-down-tray"
                            color="primary"
                        >
                            Download File
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
@else
    {{-- No file associated with this record --}}
    <x-filament::section>
        <div class="filament-library-unpreviewable">
            <div class="filament-library-unpreviewable-message">
                No file is currently associated with this item.
            </div>
        </div>
    </x-filament::section>
@endif
