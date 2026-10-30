<?php

namespace Tapp\FilamentLibrary;

use App\Models\User;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Tapp\FilamentLibrary\Enums\LibrarySort;
use Tapp\FilamentLibrary\Models\LibraryItem;
use Tapp\FilamentLibrary\Models\LibraryItemPermission;
use Tapp\FilamentLibrary\Models\LibraryItemTag;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

class FilamentLibraryPlugin implements Plugin
{
    protected static $libraryAdminCallback = null;

    protected static bool $personalFolderListenerRegistered = false;

    /**
     * @var (Closure(): bool)|bool|null
     */
    protected Closure | bool | null $navigationVisibleUsing = null;

    /**
     * @var (Closure(Builder<LibraryItem>, string): Builder<LibraryItem>)|LibrarySort|string
     */
    protected LibrarySort | Closure | string $defaultSort = LibrarySort::FoldersThenName;

    protected string $defaultSortDirection = 'asc';

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
     * Control whether Resource Library navigation items are shown.
     *
     * Defaults to always visible when unset (backward compatible).
     *
     * @param  (Closure(): bool)|bool  $condition
     */
    public function navigationVisibleUsing(Closure | bool $condition): static
    {
        $this->navigationVisibleUsing = $condition;

        return $this;
    }

    /**
     * Default order for every library listing.
     *
     * Folders come first, then files and links, and each group is alphabetical.
     * Pass a column name (`name`, `created_at`, `updated_at`), a {@see LibrarySort}
     * case, or a closure that receives the query and direction.
     *
     * People can still pick another column in the table. That choice replaces this default.
     *
     * @param  (Closure(Builder<LibraryItem>, string): Builder<LibraryItem>)|LibrarySort|string  $sort
     */
    public function defaultSort(LibrarySort | Closure | string $sort, string $direction = 'asc'): static
    {
        if (is_string($sort) && trim($sort) === '') {
            throw new InvalidArgumentException('Library sort column cannot be empty.');
        }

        $this->defaultSort = $sort;
        $this->defaultSortDirection = $direction === 'desc' ? 'desc' : 'asc';

        return $this;
    }

    public function getDefaultSort(): LibrarySort | Closure | string
    {
        return $this->defaultSort;
    }

    public function getDefaultSortDirection(): string
    {
        return $this->defaultSortDirection;
    }

    /**
     * @param  Builder<LibraryItem>  $query
     * @return Builder<LibraryItem>
     */
    public function applySort(Builder $query, ?string $direction = null): Builder
    {
        $direction = ($direction ?? $this->defaultSortDirection) === 'desc' ? 'desc' : 'asc';
        $sort = $this->defaultSort;

        if ($sort instanceof Closure) {
            return $sort($query, $direction);
        }

        if ($sort instanceof LibrarySort) {
            return $sort->apply($query, $direction);
        }

        return $query->orderBy($sort, $direction);
    }

    public function configureTableSort(Table $table): Table
    {
        $sort = $this->defaultSort;
        $direction = $this->defaultSortDirection;

        if (is_string($sort)) {
            return $table->defaultSort($sort, $direction);
        }

        if ($sort instanceof LibrarySort && $sort->column() !== null) {
            return $table->defaultSort($sort->column(), $direction);
        }

        $table = $table->defaultSort(
            fn (Builder $query, string $direction): Builder => static::current()->applySort($query, $direction),
            $direction,
        );

        if ($sort === LibrarySort::FoldersThenName) {
            $table->defaultSortOptionLabel('Folders, then name');
        }

        return $table;
    }

    public static function current(): static
    {
        $panel = Filament::getCurrentPanel();

        if ($panel?->hasPlugin('filament-library')) {
            /** @var static $plugin */
            $plugin = $panel->getPlugin('filament-library');

            return $plugin;
        }

        return static::make();
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
            static::$defaultModels[$model] ?? throw new InvalidArgumentException("Unknown filament-library model [{$model}]."),
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
            ->navigationItems($this->applyNavigationVisibility([
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
            ]));
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

    public function isNavigationVisible(): bool
    {
        if ($this->navigationVisibleUsing === null) {
            return true;
        }

        if (is_bool($this->navigationVisibleUsing)) {
            return $this->navigationVisibleUsing;
        }

        return (bool) ($this->navigationVisibleUsing)();
    }

    /**
     * @param  list<NavigationItem>  $items
     * @return list<NavigationItem>
     */
    protected function applyNavigationVisibility(array $items): array
    {
        return array_map(
            fn (NavigationItem $item): NavigationItem => $item->visible(fn (): bool => $this->isNavigationVisible()),
            $items,
        );
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
