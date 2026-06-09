@php
    /** @var array<string, mixed> $data */
    $data = $preview->parsedJson ?? [];
    $title = is_string($data['title'] ?? null) ? $data['title'] : null;
    $questions = is_array($data['questions'] ?? null) ? $data['questions'] : [];
@endphp

<div class="filament-library-json-preview filament-library-json-quiz">
    @if($title)
        <h2 class="filament-library-json-title">{{ $title }}</h2>
    @endif

    @forelse($questions as $index => $question)
        @if(! is_array($question))
            @continue
        @endif

        @php
            $stem = $question['stem'] ?? $question['question'] ?? $question['text'] ?? 'Question '.($index + 1);
            $options = $question['options'] ?? $question['choices'] ?? $question['answers'] ?? [];
            $correct = $question['correct'] ?? $question['correct_answer'] ?? $question['answer'] ?? null;
        @endphp

        <div class="filament-library-quiz-question">
            <div class="filament-library-quiz-question-number">Question {{ $index + 1 }}</div>
            <div class="filament-library-quiz-question-stem">{{ $stem }}</div>

            @if(is_array($options) && $options !== [])
                <ul class="filament-library-quiz-options">
                    @foreach($options as $optionIndex => $option)
                        @php
                            $label = is_array($option)
                                ? ($option['text'] ?? $option['label'] ?? $option['value'] ?? json_encode($option))
                                : $option;
                            $isCorrect = $correct !== null && (
                                $correct === $option
                                || $correct === $optionIndex
                                || (is_array($option) && (($option['id'] ?? null) === $correct || ($option['value'] ?? null) === $correct))
                            );
                        @endphp
                        <li @class(['filament-library-quiz-option', 'filament-library-quiz-option-correct' => $isCorrect])>
                            {{ $label }}
                            @if($isCorrect)
                                <span class="filament-library-quiz-correct-badge">Correct</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @empty
        <p class="filament-library-json-empty">No questions found in this quiz file.</p>
    @endforelse
</div>
