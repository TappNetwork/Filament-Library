<?php

namespace Tapp\FilamentLibrary;

use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationItem;
use Filament\Panel;

class FilamentLibraryPlugin implements Plugin
{
    protected static $libraryAdminCallback = null;

    public function getId(): string
    {
        return 'filament-library';
    }

    /**
     * Set a custom callback to determine if a user is a library admin.
     *
     * @param  callable  $callback  Function that receives a user and returns bool
     */
    public static function setLibraryAdminCallback(callable $callback): void
    {
        static::$libraryAdminCallback = $callback;
    }

    /**
     * Check if a user is a library admin.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     */
    public static function isLibraryAdmin($user): bool
    {
        if (! $user) {
            return false;
        }

        // Use custom callback if set
        if (static::$libraryAdminCallback) {
            return call_user_func(static::$libraryAdminCallback, $user);
        }

        // Check for config-based callback
        $configCallback = config('filament-library.admin_callback');
        if ($configCallback && is_callable($configCallback)) {
            return call_user_func($configCallback, $user);
        }

        // Default implementation - check for configured admin role
        $adminRole = config('filament-library.admin_role', 'Admin');
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole($adminRole);
        }

        return false;
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                \Tapp\FilamentLibrary\Resources\LibraryItemResource::class,
            ])
            ->navigationItems([
                NavigationItem::make('Library')
                    ->url(fn () => \Tapp\FilamentLibrary\Resources\LibraryItemResource::getUrl('index'))
                    ->icon('heroicon-o-building-library')
                    ->group('Resource Library')
                    ->sort(1)
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.library.index')),
                NavigationItem::make('Search All')
                    ->url(fn () => \Tapp\FilamentLibrary\Resources\LibraryItemResource::getUrl('search-all'))
                    ->icon('heroicon-o-magnifying-glass')
                    ->group('Resource Library')
                    ->sort(2)
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.library.search-all')),
                NavigationItem::make('My Documents')
                    ->url(fn () => \Tapp\FilamentLibrary\Resources\LibraryItemResource::getUrl('my-documents'))
                    ->icon('heroicon-o-folder')
                    ->group('Resource Library')
                    ->sort(3)
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.library.my-documents')),
                NavigationItem::make('Shared with Me')
                    ->url(fn () => \Tapp\FilamentLibrary\Resources\LibraryItemResource::getUrl('shared-with-me'))
                    ->icon('heroicon-o-share')
                    ->group('Resource Library')
                    ->sort(4)
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.library.shared-with-me')),
                NavigationItem::make('Created by Me')
                    ->url(fn () => \Tapp\FilamentLibrary\Resources\LibraryItemResource::getUrl('created-by-me'))
                    ->icon('heroicon-o-user')
                    ->group('Resource Library')
                    ->sort(5)
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.library.created-by-me')),
                NavigationItem::make('Favorites')
                    ->url(fn () => \Tapp\FilamentLibrary\Resources\LibraryItemResource::getUrl('favorites'))
                    ->icon('heroicon-o-star')
                    ->group('Resource Library')
                    ->sort(6)
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.library.favorites')),
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
