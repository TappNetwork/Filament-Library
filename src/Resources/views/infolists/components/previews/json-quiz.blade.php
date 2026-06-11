@php
    /** @var array<string, mixed> $data */
    $data = $preview->parsedJson ?? [];
    $title = is_string($data['title'] ?? null) ? $data['title'] : null;
    $questions = is_array($data['questions'] ?? null) ? $data['questions'] : [];

    $resolveIsCorrect = function (array $question, mixed $option, int $optionIndex): bool {
        if (array_key_exists('correct_index', $question)) {
            $correctIndex = $question['correct_index'];

            if (is_numeric($correctIndex)) {
                return (int) $correctIndex === $optionIndex;
            }

            if (is_string($correctIndex)) {
                $normalized = strtoupper(trim($correctIndex));

                if (strlen($normalized) === 1 && $normalized >= 'A' && $normalized <= 'Z') {
                    return $normalized === chr(65 + $optionIndex);
                }
            }

            return false;
        }

        $correct = $question['correct'] ?? $question['correct_answer'] ?? $question['answer'] ?? null;

        if ($correct === null) {
            return false;
        }

        return $correct === $option
            || $correct === $optionIndex
            || (is_array($option) && (($option['id'] ?? null) === $correct || ($option['value'] ?? null) === $correct));
    };
@endphp

<div class="filament-library-json-quiz rounded-lg bg-white p-6 text-gray-950 dark:bg-gray-900 dark:text-white">
    @if($title)
        <h2 class="mb-4 text-xl font-semibold text-gray-950 dark:text-white">{{ $title }}</h2>
    @endif

    @forelse($questions as $index => $question)
        @if(! is_array($question))
            @continue
        @endif

        @php
            $stem = $question['stem'] ?? $question['question'] ?? $question['text'] ?? 'Question '.($index + 1);
            $options = $question['options'] ?? $question['choices'] ?? $question['answers'] ?? [];
            $explanation = is_string($question['explanation'] ?? null) ? $question['explanation'] : null;
            $hasCorrectOption = false;

            if (is_array($options)) {
                foreach ($options as $optionIndex => $option) {
                    if ($resolveIsCorrect($question, $option, $optionIndex)) {
                        $hasCorrectOption = true;

                        break;
                    }
                }
            }
        @endphp

        <div @class([
            'space-y-4',
            'border-b border-gray-200 pb-6 dark:border-white/10' => ! $loop->last,
            'pb-2' => $loop->last,
        ])>
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ __('Question :number', ['number' => $index + 1]) }}
                </div>
                <p class="mt-2 text-base font-medium leading-snug text-gray-950 dark:text-white">
                    {{ $stem }}
                </p>
            </div>

            @if(is_array($options) && $options !== [])
                <ul class="m-0 flex list-none flex-col gap-2 p-0">
                    @foreach($options as $optionIndex => $option)
                        @php
                            $label = is_array($option)
                                ? ($option['text'] ?? $option['label'] ?? $option['value'] ?? json_encode($option))
                                : $option;
                            $isCorrect = $resolveIsCorrect($question, $option, $optionIndex);
                            $optionLetter = chr(65 + $optionIndex);
                        @endphp
                        <li @class([
                            'flex items-center gap-3 rounded-lg border px-4 py-2.5',
                            'border-emerald-300 bg-emerald-50 dark:border-emerald-500/50 dark:bg-emerald-950/30' => $isCorrect,
                            'border-gray-200 bg-gray-50/80 dark:border-white/10 dark:bg-white/5' => ! $isCorrect,
                        ])>
                            <span @class([
                                'flex size-5 shrink-0 items-center justify-center rounded text-[10px] font-bold leading-none',
                                'bg-emerald-500 text-white' => $isCorrect,
                                'bg-gray-200 text-gray-700 dark:bg-white/10 dark:text-gray-200' => ! $isCorrect,
                            ])>
                                @if($isCorrect)
                                    <x-filament::icon icon="heroicon-m-check" class="size-3.5" />
                                @else
                                    {{ $optionLetter }}
                                @endif
                            </span>
                            <span class="text-sm leading-snug text-gray-800 dark:text-gray-200">
                                {{ $label }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if($explanation)
                <div @class([
                    'rounded-lg border p-4',
                    'mt-4' => is_array($options) && $options !== [],
                    'border-emerald-300 bg-emerald-50 dark:border-emerald-500/50 dark:bg-emerald-950/20' => $hasCorrectOption,
                    'border-gray-200 bg-gray-50/80 dark:border-white/20 dark:bg-white/5' => ! $hasCorrectOption,
                ])>
                    @if($hasCorrectOption)
                        <p class="flex items-center gap-2 font-semibold text-emerald-800 dark:text-emerald-300">
                            <x-filament::icon icon="heroicon-m-check-circle" class="size-4" />
                            {{ __('Correct!') }}
                        </p>
                    @endif
                    <p @class([
                        'text-sm leading-relaxed text-gray-700 dark:text-gray-300',
                        'mt-2' => $hasCorrectOption,
                    ])>
                        {{ $explanation }}
                    </p>
                </div>
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('No questions found in this quiz file.') }}
        </p>
    @endforelse
</div>
