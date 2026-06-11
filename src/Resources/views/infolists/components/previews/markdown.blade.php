@php
    use Illuminate\Support\Str;

    $html = Str::markdown(mb_trim($preview->textContent ?? ''));
@endphp

<div class="filament-library-prose">
    {!! $html !!}
</div>
