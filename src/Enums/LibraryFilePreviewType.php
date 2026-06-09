<?php

declare(strict_types=1);

namespace Tapp\FilamentLibrary\Enums;

enum LibraryFilePreviewType: string
{
    case Image = 'image';
    case Pdf = 'pdf';
    case Video = 'video';
    case Audio = 'audio';
    case Markdown = 'markdown';
    case JsonQuiz = 'json_quiz';
    case JsonFlashcards = 'json_flashcards';
    case JsonMindmap = 'json_mindmap';
    case JsonGeneric = 'json_generic';
    case Unsupported = 'unsupported';

    public function isPreviewable(): bool
    {
        return $this !== self::Unsupported;
    }

    public function viewName(): string
    {
        return match ($this) {
            self::Image => 'image',
            self::Pdf => 'pdf',
            self::Video => 'video',
            self::Audio => 'audio',
            self::Markdown => 'markdown',
            self::JsonQuiz => 'json-quiz',
            self::JsonFlashcards => 'json-flashcards',
            self::JsonMindmap => 'json-mindmap',
            self::JsonGeneric => 'json-generic',
            self::Unsupported => 'download',
        };
    }
}
