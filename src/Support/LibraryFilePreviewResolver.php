<?php

declare(strict_types=1);

namespace Tapp\FilamentLibrary\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tapp\FilamentLibrary\Enums\LibraryFilePreviewType;

final class LibraryFilePreviewResolver
{
    /** @var list<string> */
    private const PREVIEWABLE_IMAGES = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];

    /** @var list<string> */
    private const PREVIEWABLE_VIDEOS = ['mp4', 'webm', 'ogg', 'avi', 'mov'];

    /** @var list<string> */
    private const PREVIEWABLE_AUDIO = ['mp3', 'wav', 'ogg', 'm4a', 'aac'];

    /** @var list<string> */
    private const PDF_MIME_TYPES = ['application/pdf', 'application/x-pdf'];

    public static function resolve(Media $media): LibraryFilePreview
    {
        $mimeType = strtolower((string) $media->mime_type);
        $extension = self::resolveExtension($media);

        if (self::isImage($mimeType, $extension)) {
            return new LibraryFilePreview(LibraryFilePreviewType::Image, $mimeType, $extension);
        }

        if (self::isPdf($mimeType, $extension)) {
            return new LibraryFilePreview(LibraryFilePreviewType::Pdf, $mimeType, $extension);
        }

        if (self::isVideo($mimeType, $extension)) {
            return new LibraryFilePreview(LibraryFilePreviewType::Video, $mimeType, $extension);
        }

        if (self::isAudio($mimeType, $extension)) {
            return new LibraryFilePreview(LibraryFilePreviewType::Audio, $mimeType, $extension);
        }

        if (self::isMarkdown($mimeType, $extension)) {
            $content = LibraryFileContentReader::read($media);

            if ($content === null) {
                return self::unsupported(
                    $mimeType,
                    $extension,
                    'This file is too large to preview. Please download to view.',
                );
            }

            return new LibraryFilePreview(
                LibraryFilePreviewType::Markdown,
                $mimeType,
                $extension,
                textContent: $content,
            );
        }

        if (self::isJson($mimeType, $extension)) {
            return self::resolveJsonPreview($media, $mimeType, $extension);
        }

        return self::unsupported(
            $mimeType,
            $extension,
            'This file type cannot be previewed. Please download to view.',
        );
    }

    public static function resolveExtension(Media $media): string
    {
        $fileNameExtension = strtolower(pathinfo((string) $media->file_name, PATHINFO_EXTENSION));

        if ($fileNameExtension !== '') {
            return $fileNameExtension;
        }

        return strtolower(pathinfo((string) $media->name, PATHINFO_EXTENSION));
    }

    private static function isImage(string $mimeType, string $extension): bool
    {
        return str_starts_with($mimeType, 'image/')
            || in_array($extension, self::PREVIEWABLE_IMAGES, true);
    }

    private static function isPdf(string $mimeType, string $extension): bool
    {
        if (in_array($mimeType, self::PDF_MIME_TYPES, true)) {
            return true;
        }

        return $extension === 'pdf';
    }

    private static function isVideo(string $mimeType, string $extension): bool
    {
        return str_starts_with($mimeType, 'video/')
            || in_array($extension, self::PREVIEWABLE_VIDEOS, true);
    }

    private static function isAudio(string $mimeType, string $extension): bool
    {
        return str_starts_with($mimeType, 'audio/')
            || in_array($extension, self::PREVIEWABLE_AUDIO, true);
    }

    private static function isMarkdown(string $mimeType, string $extension): bool
    {
        /** @var list<string> $markdownExtensions */
        $markdownExtensions = config('filament-library.preview.markdown_extensions', ['md', 'markdown', 'mdown']);

        if (in_array($extension, $markdownExtensions, true)) {
            return true;
        }

        return in_array($mimeType, ['text/markdown', 'text/x-markdown'], true);
    }

    private static function isJson(string $mimeType, string $extension): bool
    {
        return $extension === 'json'
            || in_array($mimeType, ['application/json', 'text/json'], true);
    }

    private static function resolveJsonPreview(Media $media, string $mimeType, string $extension): LibraryFilePreview
    {
        $content = LibraryFileContentReader::read($media);

        if ($content === null) {
            return self::unsupported(
                $mimeType,
                $extension,
                'This file is too large to preview. Please download to view.',
            );
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return self::unsupported(
                $mimeType,
                $extension,
                'This JSON file could not be parsed. Please download to view.',
            );
        }

        if (! is_array($decoded)) {
            return new LibraryFilePreview(
                LibraryFilePreviewType::JsonGeneric,
                $mimeType,
                $extension,
                parsedJson: ['value' => $decoded],
            );
        }

        $filenameHint = self::resolveJsonTypeFromFilename($media);
        $schemaHint = self::resolveJsonTypeFromSchema($decoded);

        $type = $filenameHint ?? $schemaHint ?? LibraryFilePreviewType::JsonGeneric;

        return new LibraryFilePreview(
            $type,
            $mimeType,
            $extension,
            parsedJson: $decoded,
        );
    }

    private static function resolveJsonTypeFromFilename(Media $media): ?LibraryFilePreviewType
    {
        $haystack = strtolower((string) $media->file_name . ' ' . (string) $media->name);

        /** @var array<string, list<string>> $patterns */
        $patterns = config('filament-library.preview.json_filename_patterns', [
            'quiz' => ['quiz'],
            'flashcards' => ['flashcard', 'flashcards'],
            'mindmap' => ['mindmap', 'mind-map', 'mind_map'],
        ]);

        foreach ($patterns['quiz'] ?? ['quiz'] as $pattern) {
            if (str_contains($haystack, strtolower($pattern))) {
                return LibraryFilePreviewType::JsonQuiz;
            }
        }

        foreach ($patterns['flashcards'] ?? ['flashcard', 'flashcards'] as $pattern) {
            if (str_contains($haystack, strtolower($pattern))) {
                return LibraryFilePreviewType::JsonFlashcards;
            }
        }

        foreach ($patterns['mindmap'] ?? ['mindmap', 'mind-map', 'mind_map'] as $pattern) {
            if (str_contains($haystack, strtolower($pattern))) {
                return LibraryFilePreviewType::JsonMindmap;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private static function resolveJsonTypeFromSchema(array $decoded): ?LibraryFilePreviewType
    {
        if (self::looksLikeQuiz($decoded)) {
            return LibraryFilePreviewType::JsonQuiz;
        }

        if (self::looksLikeFlashcards($decoded)) {
            return LibraryFilePreviewType::JsonFlashcards;
        }

        if (self::looksLikeMindmap($decoded)) {
            return LibraryFilePreviewType::JsonMindmap;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private static function looksLikeQuiz(array $decoded): bool
    {
        if (! isset($decoded['questions']) || ! is_array($decoded['questions'])) {
            return false;
        }

        foreach ($decoded['questions'] as $question) {
            if (! is_array($question)) {
                continue;
            }

            $hasPrompt = isset($question['stem']) || isset($question['question']) || isset($question['text']);
            $hasOptions = isset($question['options']) || isset($question['choices']) || isset($question['answers']);

            if ($hasPrompt && $hasOptions) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private static function looksLikeFlashcards(array $decoded): bool
    {
        if (! isset($decoded['cards']) || ! is_array($decoded['cards'])) {
            return false;
        }

        foreach ($decoded['cards'] as $card) {
            if (! is_array($card)) {
                continue;
            }

            $hasFront = isset($card['front']) || isset($card['term']) || isset($card['question']);
            $hasBack = isset($card['back']) || isset($card['definition']) || isset($card['answer']);

            if ($hasFront && $hasBack) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private static function looksLikeMindmap(array $decoded): bool
    {
        if (isset($decoded['nodes'], $decoded['edges']) && is_array($decoded['nodes'])) {
            return true;
        }

        if (isset($decoded['root']) && is_array($decoded['root'])) {
            return true;
        }

        if (self::hasNestedChildren($decoded)) {
            return true;
        }

        return isset($decoded['children']) && is_array($decoded['children']);
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private static function hasNestedChildren(array $decoded): bool
    {
        if (! isset($decoded['children']) || ! is_array($decoded['children'])) {
            return false;
        }

        foreach ($decoded['children'] as $child) {
            if (is_array($child) && (isset($child['children']) || isset($child['label']) || isset($child['title']) || isset($child['name']))) {
                return true;
            }
        }

        return false;
    }

    private static function unsupported(string $mimeType, string $extension, string $message): LibraryFilePreview
    {
        return new LibraryFilePreview(
            LibraryFilePreviewType::Unsupported,
            $mimeType,
            $extension,
            fallbackMessage: $message,
        );
    }
}
