<?php

namespace Tapp\FilamentLibrary;

use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationItem;
use Filament\Panel;

class FilamentLibraryPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-library';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                \Tapp\FilamentLibrary\Resources\LibraryItemResource::class,
            ])
            ->navigationItems([
                NavigationItem::make('Library')
                    ->url('/library')
                    ->icon('heroicon-o-building-library')
                    ->group('Resource Library')
                    ->sort(1)
                    ->isActiveWhen(fn () => request()->is('library')),
                NavigationItem::make('Search All')
                    ->url('/library/search-all')
                    ->icon('heroicon-o-magnifying-glass')
                    ->group('Resource Library')
                    ->sort(2)
                    ->isActiveWhen(fn () => request()->is('library/search-all')),
                NavigationItem::make('My Documents')
                    ->url('/library/my-documents')
                    ->icon('heroicon-o-folder')
                    ->group('Resource Library')
                    ->sort(3)
                    ->isActiveWhen(fn () => request()->is('library/my-documents')),
                NavigationItem::make('Shared with Me')
                    ->url('/library/shared-with-me')
                    ->icon('heroicon-o-share')
                    ->group('Resource Library')
                    ->sort(4)
                    ->isActiveWhen(fn () => request()->is('library/shared-with-me')),
                NavigationItem::make('Created by Me')
                    ->url('/library/created-by-me')
                    ->icon('heroicon-o-user')
                    ->group('Resource Library')
                    ->sort(5)
                    ->isActiveWhen(fn () => request()->is('library/created-by-me')),
            ]);
    }

    public function boot(Panel $panel): void
    {
        // Ensure users have personal folders when they access the library
        \App\Models\User::created(function ($user) {
            \Tapp\FilamentLibrary\Models\LibraryItem::ensurePersonalFolder($user);
        });
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
