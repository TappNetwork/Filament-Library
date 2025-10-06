@php
    $tags = $getRecord()->tags;
@endphp

<div class="py-2 flex flex-wrap gap-2">
    @foreach($tags as $tag)
        <x-filament::badge :color="$tag->getColorFromId()">
            {{ $tag->name }}
        </x-filament::badge>
    @endforeach
</div>
