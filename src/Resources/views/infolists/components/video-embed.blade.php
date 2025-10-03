@php
    $url = $getState();
    $embedHtml = $getVideoEmbedHtml($url);
@endphp

@if($embedHtml)
    <div class="w-full">
        {!! $embedHtml !!}
    </div>
@else
    <div class="text-gray-500 text-sm">
        Video preview not available for this URL.
    </div>
@endif

<style>
    .video-container {
        position: relative;
        width: 100%;
        height: 0;
        padding-bottom: 56.25%; /* 16:9 aspect ratio */
        overflow: hidden;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .video-container iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: 0;
    }
</style>
