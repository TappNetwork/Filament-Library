@php
    use Illuminate\Support\Str;

    $html = Str::markdown($preview->textContent ?? '');
@endphp

<div class="filament-library-prose">
    {!! $html !!}
</div>
