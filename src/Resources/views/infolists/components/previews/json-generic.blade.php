@php
    /** @var array<string, mixed> $data */
    $data = $preview->parsedJson ?? [];
@endphp

<div class="filament-library-json-preview filament-library-json-generic">
    @include('filament-library::infolists.components.previews.partials.json-tree', [
        'data' => $data,
        'depth' => 0,
    ])
</div>
