<x-filament-infolists::entry-wrapper
    :entry="$entry"
    :id="$getId()"
    :state-path="$getStatePath()"
>
    <div class="fi-in-entry-wrp-label">
        {{ $getLabel() }}
    </div>

    <div class="fi-in-entry-wrp-content">
        {!! $getVideoEmbedHtml($getState()) !!}
    </div>
</x-filament-infolists::entry-wrapper>
