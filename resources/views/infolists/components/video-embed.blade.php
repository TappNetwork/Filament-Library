<x-filament-infolists::entry-wrapper
    :entry="$entry"
    :id="$getId()"
    :state-path="$getStatePath()"
>
    <div class="fi-in-entry-wrp-content" style="padding: 0; margin: 0;">
        {!! $getVideoEmbedHtml($getState()) !!}
    </div>
</x-filament-infolists::entry-wrapper>
