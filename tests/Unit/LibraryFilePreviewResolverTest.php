<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tapp\FilamentLibrary\Enums\LibraryFilePreviewType;
use Tapp\FilamentLibrary\Support\LibraryFilePreviewResolver;

function previewMedia(array $attributes, ?string $diskPath = null, ?string $content = null): Media
{
    Storage::fake('public');

    if ($diskPath !== null && $content !== null) {
        Storage::disk('public')->put($diskPath, $content);
    }

    $media = new Media(array_merge([
        'disk' => 'public',
        'conversions_disk' => 'public',
        'collection_name' => 'files',
        'manipulations' => [],
        'custom_properties' => [],
        'generated_conversions' => [],
        'responsive_images' => [],
    ], $attributes));

    if ($diskPath !== null) {
        $media->forceFill([
            'id' => 1,
        ]);

        $media->setRawAttributes(array_merge($media->getAttributes(), [
            'id' => 1,
        ]), true);

        $fullPath = Storage::disk('public')->path($diskPath);

        $media = Mockery::mock($media)->makePartial();
        $media->shouldReceive('getPath')->andReturn($fullPath);
    }

    return $media;
}

it('detects pdf previews from file_name when display name lacks an extension', function (): void {
    $media = previewMedia([
        'file_name' => 'hashed-slides.pdf',
        'name' => 'Q1 Sales Slides',
        'mime_type' => 'application/octet-stream',
        'size' => 1024,
    ]);

    $preview = LibraryFilePreviewResolver::resolve($media);

    expect($preview->type)->toBe(LibraryFilePreviewType::Pdf)
        ->and($preview->isPreviewable())->toBeTrue();
});

it('detects markdown previews and reads content from storage', function (): void {
    $content = "# Atlas Notes\n\n- First point\n- Second point";

    $media = previewMedia([
        'file_name' => 'notes.md',
        'name' => 'Atlas Notes',
        'mime_type' => 'text/markdown',
        'size' => strlen($content),
    ], 'library/notes.md', $content);

    $preview = LibraryFilePreviewResolver::resolve($media);

    expect($preview->type)->toBe(LibraryFilePreviewType::Markdown)
        ->and($preview->textContent)->toBe($content);
});

it('detects quiz json from filename patterns', function (): void {
    $content = json_encode([
        'title' => 'Product Quiz',
        'questions' => [
            [
                'stem' => 'What is Laravel?',
                'options' => ['A framework', 'A database'],
                'correct' => 'A framework',
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $media = previewMedia([
        'file_name' => 'topic-quiz.json',
        'name' => 'Topic Quiz',
        'mime_type' => 'application/json',
        'size' => strlen($content),
    ], 'library/topic-quiz.json', $content);

    $preview = LibraryFilePreviewResolver::resolve($media);

    expect($preview->type)->toBe(LibraryFilePreviewType::JsonQuiz)
        ->and($preview->parsedJson['title'] ?? null)->toBe('Product Quiz');
});

it('detects flashcards json from schema heuristics', function (): void {
    $content = json_encode([
        'cards' => [
            ['front' => 'Term', 'back' => 'Definition'],
        ],
    ], JSON_THROW_ON_ERROR);

    $media = previewMedia([
        'file_name' => 'study-set.json',
        'name' => 'Study Set',
        'mime_type' => 'application/json',
        'size' => strlen($content),
    ], 'library/study-set.json', $content);

    $preview = LibraryFilePreviewResolver::resolve($media);

    expect($preview->type)->toBe(LibraryFilePreviewType::JsonFlashcards);
});

it('detects mind map json from nested children', function (): void {
    $content = json_encode([
        'title' => 'Topic Map',
        'label' => 'Root',
        'children' => [
            ['label' => 'Branch A', 'children' => [['label' => 'Leaf']]],
        ],
    ], JSON_THROW_ON_ERROR);

    $media = previewMedia([
        'file_name' => 'topic-mindmap.json',
        'name' => 'Topic Map',
        'mime_type' => 'application/json',
        'size' => strlen($content),
    ], 'library/topic-mindmap.json', $content);

    $preview = LibraryFilePreviewResolver::resolve($media);

    expect($preview->type)->toBe(LibraryFilePreviewType::JsonMindmap);
});

it('falls back to unsupported when text content exceeds preview limit', function (): void {
    config()->set('filament-library.preview.text_max_bytes', 10);

    $content = str_repeat('a', 20);

    $media = previewMedia([
        'file_name' => 'notes.md',
        'name' => 'Large Notes',
        'mime_type' => 'text/markdown',
        'size' => strlen($content),
    ], 'library/large-notes.md', $content);

    $preview = LibraryFilePreviewResolver::resolve($media);

    expect($preview->type)->toBe(LibraryFilePreviewType::Unsupported)
        ->and($preview->fallbackMessage)->toContain('too large');
});

it('falls back to unsupported when json is invalid', function (): void {
    $content = '{not valid json';

    $media = previewMedia([
        'file_name' => 'broken.json',
        'name' => 'Broken JSON',
        'mime_type' => 'application/json',
        'size' => strlen($content),
    ], 'library/broken.json', $content);

    $preview = LibraryFilePreviewResolver::resolve($media);

    expect($preview->type)->toBe(LibraryFilePreviewType::Unsupported)
        ->and($preview->fallbackMessage)->toContain('could not be parsed');
});

it('renders markdown preview blade with formatted html', function (): void {
    $content = "# Heading\n\nParagraph text.";

    $media = previewMedia([
        'file_name' => 'notes.md',
        'name' => 'Notes',
        'mime_type' => 'text/markdown',
        'size' => strlen($content),
    ], 'library/notes.md', $content);

    $preview = LibraryFilePreviewResolver::resolve($media);

    $html = view('filament-library::infolists.components.previews.markdown', [
        'media' => $media,
        'fileUrl' => 'https://example.test/notes.md',
        'preview' => $preview,
    ])->render();

    expect($html)->toContain('<h1')
        ->and($html)->toContain('Paragraph text.');
});
