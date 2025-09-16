<x-filament-infolists::entry-wrapper
    :entry="$entry"
    :id="$getId()"
    :state-path="$getStatePath()"
>
    <div class="fi-in-entry-wrp-content -mx-6 -my-4">
        {!! $getVideoEmbedHtml($getState()) !!}
    </div>
</x-filament-infolists::entry-wrapper>
