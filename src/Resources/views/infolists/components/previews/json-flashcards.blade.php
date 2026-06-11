@php
    /** @var array<string, mixed> $data */
    $data = $preview->parsedJson ?? [];
    $title = is_string($data['title'] ?? null) ? $data['title'] : null;
    $cards = is_array($data['cards'] ?? null) ? $data['cards'] : [];
@endphp

<div class="filament-library-json-flashcards rounded-lg bg-white p-6 text-gray-950 dark:bg-gray-900 dark:text-white">
    @if($title)
        <h2 class="mb-4 text-xl font-semibold text-gray-950 dark:text-white">{{ $title }}</h2>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($cards as $index => $card)
            @if(! is_array($card))
                @continue
            @endif

            @php
                $front = $card['front'] ?? $card['term'] ?? $card['question'] ?? 'Card '.($index + 1);
                $back = $card['back'] ?? $card['definition'] ?? $card['answer'] ?? '';
            @endphp

            <div class="flex min-h-48 flex-col overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">
                <div class="flex flex-1 flex-col border-b border-gray-200 bg-gray-50/80 p-4 dark:border-white/10 dark:bg-white/5">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('Front') }}
                    </span>
                    <div class="mt-1 flex flex-1 items-start text-sm leading-relaxed text-gray-800 dark:text-gray-200">
                        {{ $front }}
                    </div>
                </div>
                <div class="flex flex-1 flex-col p-4">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('Back') }}
                    </span>
                    <div class="mt-1 flex flex-1 items-start text-sm leading-relaxed text-gray-800 dark:text-gray-200">
                        {{ $back }}
                    </div>
                </div>
            </div>
        @empty
            <p class="col-span-full text-sm text-gray-500 dark:text-gray-400">
                {{ __('No flashcards found in this file.') }}
            </p>
        @endforelse
    </div>
</div>
