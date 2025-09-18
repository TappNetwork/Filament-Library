@php
    $media = $record->getFirstMedia('files');
    $mimeType = $media?->mime_type;
    $fileUrl = $media?->getUrl();

    // Ensure HTTPS URLs to avoid mixed content issues
    if ($fileUrl && str_starts_with($fileUrl, 'http://')) {
        $fileUrl = str_replace('http://', 'https://', $fileUrl);
    }

    // Get file extension for better type detection
    $extension = strtolower(pathinfo($media?->name, PATHINFO_EXTENSION));
@endphp

@if($media)
    <div class="w-full">
        @if(str_starts_with($mimeType, 'image/'))
            {{-- Image preview --}}
            <img src="{{ $fileUrl }}" alt="{{ $media->name }}" class="max-w-full h-auto rounded-lg shadow-lg">
        @elseif($mimeType === 'application/pdf' || $extension === 'pdf')
            {{-- PDF preview --}}
            <iframe src="{{ $fileUrl }}#toolbar=0&navpanes=0&scrollbar=1" class="w-full h-96 border rounded-lg" title="{{ $media->name }}"></iframe>
        @elseif($extension === 'md' || $extension === 'markdown')
            {{-- Markdown preview --}}
            <div class="border rounded-lg p-4 bg-white">
                <div class="mb-2 text-sm text-gray-500">Markdown Preview:</div>
                <iframe src="{{ $fileUrl }}" class="w-full h-96 border rounded-lg" title="{{ $media->name }}"></iframe>
            </div>
        @elseif(str_starts_with($mimeType, 'text/') || in_array($extension, ['txt', 'csv', 'json', 'xml', 'html', 'css', 'js']))
            {{-- Text file preview --}}
            <div class="border rounded-lg p-4 bg-white">
                <div class="mb-2 text-sm text-gray-500">Text Preview:</div>
                <iframe src="{{ $fileUrl }}" class="w-full h-96 border rounded-lg" title="{{ $media->name }}"></iframe>
            </div>
        @else
            {{-- Generic file info --}}
            <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                <div class="text-gray-500 mb-4">
                    <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $media->name }}</h3>
                <p class="text-sm text-gray-500 mb-4">{{ number_format($media->size / 1024 / 1024, 2) }} MB</p>
                <a href="{{ $fileUrl }}" target="_blank" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download File
                </a>
            </div>
        @endif
    </div>
@endif
