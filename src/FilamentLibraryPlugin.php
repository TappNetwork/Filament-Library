<?php

namespace Tapp\FilamentLibrary;

use App\Models\User;
use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;
use Tapp\FilamentLibrary\Models\LibraryItem;
use Tapp\FilamentLibrary\Models\LibraryItemPermission;
use Tapp\FilamentLibrary\Models\LibraryItemTag;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class FilamentLibraryPlugin implements Plugin
{
    protected static $libraryAdminCallback = null;

    protected static bool $personalFolderListenerRegistered = false;

    /**
     * @var array<string, class-string>
     */
    protected static array $defaultModels = [
        'LibraryItem' => LibraryItem::class,
        'LibraryItemPermission' => LibraryItemPermission::class,
        'LibraryItemTag' => LibraryItemTag::class,
    ];

    public function getId(): string
    {
        return 'filament-library';
    }

    /**
     * Set a custom callback to determine if a user is a library admin.
     *
     * Pass null to clear a previously registered callback.
     *
     * @param  (callable(Authenticatable): bool)|null  $callback
     */
    public static function setLibraryAdminCallback(?callable $callback): void
    {
        static::$libraryAdminCallback = $callback;
    }

    /**
     * Check if a user is a library admin.
     *
     * @param  Authenticatable|null  $user
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
     * @return class-string<LibraryItemResource>
     */
    public static function libraryItemResourceClass(): string
    {
        /** @var class-string<LibraryItemResource> $resourceClass */
        $resourceClass = config(
            'filament-library.resources.LibraryItemResource',
            LibraryItemResource::class,
        );

        return $resourceClass;
    }

    /**
     * @return class-string
     */
    public static function modelClass(string $model): string
    {
        /** @var class-string $modelClass */
        $modelClass = config(
            "filament-library.models.{$model}",
            static::$defaultModels[$model] ?? throw new \InvalidArgumentException("Unknown filament-library model [{$model}]."),
        );

        return $modelClass;
    }

    /**
     * @return class-string<LibraryItem>
     */
    public static function libraryItemModelClass(): string
    {
        /** @var class-string<LibraryItem> $modelClass */
        $modelClass = static::modelClass('LibraryItem');

        return $modelClass;
    }

    /**
     * @return class-string<LibraryItemPermission>
     */
    public static function libraryItemPermissionModelClass(): string
    {
        /** @var class-string<LibraryItemPermission> $modelClass */
        $modelClass = static::modelClass('LibraryItemPermission');

        return $modelClass;
    }

    /**
     * @return class-string<LibraryItemTag>
     */
    public static function libraryItemTagModelClass(): string
    {
        /** @var class-string<LibraryItemTag> $modelClass */
        $modelClass = static::modelClass('LibraryItemTag');

        return $modelClass;
    }

    public function register(Panel $panel): void
    {
        $panelId = $panel->getId();
        $libraryItemResourceClass = static::libraryItemResourceClass();

        $panel
            ->resources(
                array_values(config('filament-library.resources', [
                    LibraryItemResource::class,
                ])),
            )
            ->navigationItems([
                NavigationItem::make('Library')
                    ->url(fn () => $libraryItemResourceClass::getUrl('index'))
                    ->icon('heroicon-o-building-library')
                    ->group('Resource Library')
                    ->sort(1)
                    ->isActiveWhen(fn () => request()->routeIs("filament.{$panelId}.resources.library.index")),
                NavigationItem::make('Search All')
                    ->url(fn () => $libraryItemResourceClass::getUrl('search-all'))
                    ->icon('heroicon-o-magnifying-glass')
                    ->group('Resource Library')
                    ->sort(2)
                    ->isActiveWhen(fn () => request()->routeIs("filament.{$panelId}.resources.library.search-all")),
                NavigationItem::make('My Documents')
                    ->url(fn () => $libraryItemResourceClass::getUrl('my-documents'))
                    ->icon('heroicon-o-folder')
                    ->group('Resource Library')
                    ->sort(3)
                    ->isActiveWhen(fn () => request()->routeIs("filament.{$panelId}.resources.library.my-documents")),
                ...static::sharedWithMeNavigationItem($panelId, $libraryItemResourceClass),
                NavigationItem::make('Created by Me')
                    ->url(fn () => $libraryItemResourceClass::getUrl('created-by-me'))
                    ->icon('heroicon-o-user')
                    ->group('Resource Library')
                    ->sort(5)
                    ->isActiveWhen(fn () => request()->routeIs("filament.{$panelId}.resources.library.created-by-me")),
                NavigationItem::make('Favorites')
                    ->url(fn () => $libraryItemResourceClass::getUrl('favorites'))
                    ->icon('heroicon-o-star')
                    ->group('Resource Library')
                    ->sort(6)
                    ->isActiveWhen(fn () => request()->routeIs("filament.{$panelId}.resources.library.favorites")),
            ]);
    }

    public function boot(Panel $panel): void
    {
        if (! config('filament-library.personal_folder.auto_create_on_user_created', true)) {
            return;
        }

        if (static::$personalFolderListenerRegistered) {
            return;
        }

        static::$personalFolderListenerRegistered = true;

        /** @var class-string<User> $userModel */
        $userModel = config('filament-library.user_model', User::class);

        $libraryItemModel = static::libraryItemModelClass();

        // Optionally provision a personal folder when a user is created
        $userModel::created(function ($user) use ($libraryItemModel): void {
            $libraryItemModel::ensurePersonalFolder($user);
        });
    }

    /**
     * @param  class-string<LibraryItemResource>  $libraryItemResourceClass
     * @return array<int, NavigationItem>
     */
    protected static function sharedWithMeNavigationItem(string $panelId, string $libraryItemResourceClass): array
    {
        if (! config('filament-library.sharing.shared_with_me', true)) {
            return [];
        }

        return [
            NavigationItem::make('Shared with Me')
                ->url(fn () => $libraryItemResourceClass::getUrl('shared-with-me'))
                ->icon('heroicon-o-share')
                ->group('Resource Library')
                ->sort(4)
                ->isActiveWhen(fn () => request()->routeIs("filament.{$panelId}.resources.library.shared-with-me")),
        ];
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
