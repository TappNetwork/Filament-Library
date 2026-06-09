<?php

declare(strict_types=1);

namespace Tapp\FilamentLibrary\Support;

use Tapp\FilamentLibrary\Enums\LibraryFilePreviewType;

final class LibraryFilePreview
{
    /**
     * @param  array<string, mixed>|null  $parsedJson
     */
    public function __construct(
        public readonly LibraryFilePreviewType $type,
        public readonly string $mimeType,
        public readonly string $extension,
        public readonly ?string $textContent = null,
        public readonly ?array $parsedJson = null,
        public readonly ?string $fallbackMessage = null,
    ) {}

    public function isPreviewable(): bool
    {
        return $this->type->isPreviewable();
    }
}
