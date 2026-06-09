<div class="filament-library-audio-preview">
    <div class="filament-library-audio-preview-label">Audio Preview:</div>
    <audio controls class="w-full">
        <source src="{{ $fileUrl }}" type="{{ $preview->mimeType }}">
        Your browser does not support the audio tag.
    </audio>
</div>
