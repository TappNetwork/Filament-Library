<?php

namespace Tapp\FilamentLibrary;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentLibraryPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-library';
    }

    public function register(Panel $panel): void
    {
        // Register resources, pages, widgets, etc.
    }

    public function boot(Panel $panel): void
    {
        // Boot any services, register listeners, etc.
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
