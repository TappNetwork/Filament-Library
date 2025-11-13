<?php

namespace Tapp\FilamentLibrary;

use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationItem;
use Filament\Panel;

class FilamentLibraryPlugin implements Plugin
{
    protected static $libraryAdminCallback = null;

    protected static $roleCheckerCallback = null;

    protected static $availableRolesCallback = null;

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

    /**
     * Set a custom callback to check if a user has a specific role.
     *
     * @param  callable  $callback  Function that receives a user and role name, returns bool
     */
    public static function setRoleCheckerCallback(callable $callback): void
    {
        static::$roleCheckerCallback = $callback;
    }

    /**
     * Check if a user has a specific role.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     */
    public static function hasRole($user, string $roleName): bool
    {
        if (! $user) {
            return false;
        }

        // Use custom callback if set
        if (static::$roleCheckerCallback) {
            return call_user_func(static::$roleCheckerCallback, $user, $roleName);
        }

        // Check for config-based callback
        $configCallback = config('filament-library.role_checker_callback');
        if ($configCallback && is_callable($configCallback)) {
            return call_user_func($configCallback, $user, $roleName);
        }

        // Default implementation - check if user has hasRole method
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole($roleName);
        }

        return false;
    }

    /**
     * Set a custom callback to get available roles for selection.
     *
     * @param  callable  $callback  Function that returns an array of role names => labels
     */
    public static function setAvailableRolesCallback(callable $callback): void
    {
        static::$availableRolesCallback = $callback;
    }

    /**
     * Get available roles for selection.
     *
     * @return array<string, string> Array of role names => labels
     */
    public static function getAvailableRoles(): array
    {
        // Use custom callback if set
        if (static::$availableRolesCallback) {
            return call_user_func(static::$availableRolesCallback);
        }

        // Check for config-based callback
        $configCallback = config('filament-library.available_roles_callback');
        if ($configCallback && is_callable($configCallback)) {
            return call_user_func($configCallback);
        }

        return [];
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
                NavigationItem::make('Favorites')
                    ->url('/library/favorites')
                    ->icon('heroicon-o-star')
                    ->group('Resource Library')
                    ->sort(6)
                    ->isActiveWhen(fn () => request()->is('library/favorites')),
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
