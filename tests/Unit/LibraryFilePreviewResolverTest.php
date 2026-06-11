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

it('renders flashcards preview with theme-aware layout', function (): void {
    $content = json_encode([
        'title' => 'Study Flashcards',
        'cards' => [
            ['front' => 'Term', 'back' => 'Definition'],
        ],
    ], JSON_THROW_ON_ERROR);

    $media = previewMedia([
        'file_name' => 'flashcard-set.json',
        'name' => 'Flashcard Set',
        'mime_type' => 'application/json',
        'size' => strlen($content),
    ], 'library/flashcard-set.json', $content);

    $preview = LibraryFilePreviewResolver::resolve($media);

    $html = view('filament-library::infolists.components.previews.json-flashcards', [
        'media' => $media,
        'fileUrl' => 'https://example.test/flashcard-set.json',
        'preview' => $preview,
    ])->render();

    expect($html)
        ->toContain('Study Flashcards')
        ->toContain('Term')
        ->toContain('Definition')
        ->toContain('lg:grid-cols-3')
        ->toContain('flex-1')
        ->toContain('bg-white')
        ->toContain('dark:bg-gray-900');
});

it('detects mind map json from branches and leaves schema', function (): void {
    $content = json_encode([
        'root_topic' => 'Product Overview',
        'branches' => [
            [
                'title' => 'Getting Started',
                'leaves' => [
                    [
                        'title' => 'Installation',
                        'elaboration' => 'Install dependencies with Composer before running the application.',
                    ],
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $media = previewMedia([
        'file_name' => 'product-overview.json',
        'name' => 'Product Overview Mind Map',
        'mime_type' => 'application/json',
        'size' => strlen($content),
    ], 'library/product-overview.json', $content);

    $preview = LibraryFilePreviewResolver::resolve($media);

    expect($preview->type)->toBe(LibraryFilePreviewType::JsonMindmap);
});

it('renders branch mind map preview with interactive concept panel', function (): void {
    $content = json_encode([
        'root_topic' => 'Product Overview',
        'branches' => [
            [
                'title' => 'Getting Started',
                'leaves' => [
                    [
                        'title' => 'Installation',
                        'elaboration' => 'Install dependencies with Composer before running the application.',
                    ],
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $media = previewMedia([
        'file_name' => 'product-overview.json',
        'name' => 'Product Overview Mind Map',
        'mime_type' => 'application/json',
        'size' => strlen($content),
    ], 'library/product-overview.json', $content);

    $preview = LibraryFilePreviewResolver::resolve($media);

    $html = view('filament-library::infolists.components.previews.json-mindmap', [
        'media' => $media,
        'fileUrl' => 'https://example.test/product-overview.json',
        'preview' => $preview,
    ])->render();

    expect($html)
        ->toContain('Product Overview')
        ->toContain('Getting Started')
        ->toContain('Installation')
        ->toContain('Install dependencies with Composer before running the application.')
        ->toContain('selectedId')
        ->toContain('bg-white')
        ->toContain('dark:bg-gray-900');
});

it('renders legacy mind map preview with nested children', function (): void {
    $content = json_encode([
        'title' => 'Sales Process Map',
        'label' => 'Sales Process',
        'children' => [
            ['label' => 'Discovery', 'children' => [['label' => 'Needs analysis']]],
        ],
    ], JSON_THROW_ON_ERROR);

    $media = previewMedia([
        'file_name' => 'topic-mindmap.json',
        'name' => 'Topic Map',
        'mime_type' => 'application/json',
        'size' => strlen($content),
    ], 'library/topic-mindmap.json', $content);

    $preview = LibraryFilePreviewResolver::resolve($media);

    $html = view('filament-library::infolists.components.previews.json-mindmap', [
        'media' => $media,
        'fileUrl' => 'https://example.test/topic-mindmap.json',
        'preview' => $preview,
    ])->render();

    expect($html)
        ->toContain('Sales Process Map')
        ->toContain('Sales Process')
        ->toContain('Discovery')
        ->toContain('Needs analysis')
        ->toContain('dark:bg-gray-900');
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
        ->and($html)->toContain('Paragraph text.')
        ->and($html)->toContain('filament-library-prose');
});

it('renders quiz preview with legacy correct answer keys', function (): void {
    $content = json_encode([
        'title' => 'Product Quiz',
        'questions' => [
            [
                'stem' => 'What is Laravel?',
                'options' => ['A framework', 'A database'],
                'correct' => 'A framework',
                'explanation' => 'Laravel is a PHP web framework.',
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

    $html = view('filament-library::infolists.components.previews.json-quiz', [
        'media' => $media,
        'fileUrl' => 'https://example.test/topic-quiz.json',
        'preview' => $preview,
    ])->render();

    expect($html)
        ->toContain('A framework')
        ->toContain('Laravel is a PHP web framework.')
        ->toContain('Correct!')
        ->toContain('bg-white')
        ->toContain('dark:bg-gray-900');
});

it('renders quiz preview with correct_index and explanation', function (): void {
    $content = json_encode([
        'title' => 'Platform Knowledge Quiz',
        'questions' => [
            [
                'question' => 'Which language powers this backend?',
                'options' => [
                    'PHP',
                    'Python',
                    'Ruby',
                    'Java',
                ],
                'correct_index' => 0,
                'explanation' => 'PHP is the primary language used by Laravel applications.',
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $media = previewMedia([
        'file_name' => 'platform-quiz.json',
        'name' => 'Platform Quiz',
        'mime_type' => 'application/json',
        'size' => strlen($content),
    ], 'library/platform-quiz.json', $content);

    $preview = LibraryFilePreviewResolver::resolve($media);

    $html = view('filament-library::infolists.components.previews.json-quiz', [
        'media' => $media,
        'fileUrl' => 'https://example.test/platform-quiz.json',
        'preview' => $preview,
    ])->render();

    expect($html)
        ->toContain('PHP')
        ->toContain('PHP is the primary language used by Laravel applications.')
        ->toContain('border-emerald-300')
        ->toContain('Correct!');
});

it('renders download preview button with a valid signed url href', function (): void {
    $signedUrl = 'https://example.test/file.txt?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Signature=abc123';

    $html = view('filament-library::infolists.components.previews.download', [
        'fileUrl' => $signedUrl,
        'message' => 'This file type cannot be previewed. Please download to view.',
    ])->render();

    preg_match('/href="([^"]+)"/', $html, $matches);

    expect($matches)->not->toBeEmpty()
        ->and(html_entity_decode($matches[1], ENT_QUOTES))->toBe($signedUrl)
        ->and($html)->not->toContain('&amp;amp;');
});
