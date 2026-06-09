<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    Config::set('filament-library.preview.text_max_bytes', 2 * 1024 * 1024);
});
