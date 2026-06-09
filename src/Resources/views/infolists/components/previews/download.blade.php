<x-filament::section>
    <div class="filament-library-unpreviewable">
        <div class="filament-library-unpreviewable-message">
            {{ $message }}
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
