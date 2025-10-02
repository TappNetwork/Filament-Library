@php
    $tags = $getRecord()->tags;
    $maxVisible = 3;
    $visibleTags = $tags->take($maxVisible);
    $remainingCount = $tags->count() - $maxVisible;
@endphp

<div class="py-1 flex flex-wrap gap-1">
    @foreach($visibleTags as $tag)
        <x-filament::badge
            :color="$tag->getColorFromId()"
            size="sm"
            class="text-xs"
        >
            {{ $tag->name }}
        </x-filament::badge>
    @endforeach

    @if($remainingCount > 0)
        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
            +{{ $remainingCount }} more
        </span>
    @endif
</div>
