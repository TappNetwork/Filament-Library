@php
    /** @var array<string, mixed> $data */
    $data = $preview->parsedJson ?? [];
    $title = is_string($data['title'] ?? null) ? $data['title'] : null;
    $cards = is_array($data['cards'] ?? null) ? $data['cards'] : [];
@endphp

<div class="filament-library-json-preview filament-library-json-flashcards">
    @if($title)
        <h2 class="filament-library-json-title">{{ $title }}</h2>
    @endif

    <div class="filament-library-flashcards-grid">
        @forelse($cards as $index => $card)
            @if(! is_array($card))
                @continue
            @endif

            @php
                $front = $card['front'] ?? $card['term'] ?? $card['question'] ?? 'Card '.($index + 1);
                $back = $card['back'] ?? $card['definition'] ?? $card['answer'] ?? '';
            @endphp

            <div class="filament-library-flashcard">
                <div class="filament-library-flashcard-front">
                    <span class="filament-library-flashcard-label">Front</span>
                    <div>{{ $front }}</div>
                </div>
                <div class="filament-library-flashcard-back">
                    <span class="filament-library-flashcard-label">Back</span>
                    <div>{{ $back }}</div>
                </div>
            </div>
        @empty
            <p class="filament-library-json-empty">No flashcards found in this file.</p>
        @endforelse
    </div>
</div>
