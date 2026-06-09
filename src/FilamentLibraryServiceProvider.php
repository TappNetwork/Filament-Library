<?php

namespace Tapp\FilamentLibrary;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tapp\FilamentLibrary\Commands\FilamentLibraryCommand;
use Tapp\FilamentLibrary\Commands\SeedLibraryCommand;
use Tapp\FilamentLibrary\Events\LibraryFileRestored;
use Tapp\FilamentLibrary\Events\LibraryFileStored;
use Tapp\FilamentLibrary\Middleware\RedirectToCorrectEditPage;
use Tapp\FilamentLibrary\Models\LibraryItem;
use Tapp\FilamentLibrary\Policies\LibraryItemPolicy;

class FilamentLibraryServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-library';

    public static string $viewNamespace = 'filament-library';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('TappNetwork/Filament-Library');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        // Views are loaded manually to avoid automatic publishing
        // Users can publish them manually with: php artisan vendor:publish --tag=filament-library-views
    }

    public function packageRegistered(): void {}

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Register middleware
        $this->app['router']->pushMiddlewareToGroup('web', RedirectToCorrectEditPage::class);

        // Register the policy for the default package model only; host apps
        // provide their own policy when overriding models.LibraryItem.
        if (FilamentLibraryPlugin::libraryItemModelClass() === LibraryItem::class) {
            $this->app['Illuminate\Contracts\Auth\Access\Gate']->policy(LibraryItem::class, LibraryItemPolicy::class);
        }

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/filament-library/{$file->getFilename()}"),
                ], 'filament-library-stubs');
            }
        }

        // Load views manually from src directory
        $this->loadViewsFrom(__DIR__ . '/Resources/views', static::$viewNamespace);

        $this->registerLibraryFileStoredEvent();
        $this->registerLibraryFileRestoredEvent();

        // Publish views manually (optional)
        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/filament-library'),
        ], 'filament-library-views');

        // Testing - No custom test mixins needed
    }

    protected function getAssetPackageName(): ?string
    {
        return 'tapp/filament-library';
    }

    /**
     * @return array<Css|Js>
     */
    protected function getAssets(): array
    {
        $assets = [];

        // Register our custom CSS file
        if (file_exists(__DIR__ . '/../resources/css/filament-library.css')) {
            $assets[] = Css::make('filament-library-styles', __DIR__ . '/../resources/css/filament-library.css');
        }

        // Only register dist assets if they exist
        if (file_exists(__DIR__ . '/../resources/dist/filament-library.css')) {
            $assets[] = Css::make('filament-library-dist-styles', __DIR__ . '/../resources/dist/filament-library.css');
        }

        if (file_exists(__DIR__ . '/../resources/dist/filament-library.js')) {
            $assets[] = Js::make('filament-library-scripts', __DIR__ . '/../resources/dist/filament-library.js');
        }

        return $assets;
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            FilamentLibraryCommand::class,
            SeedLibraryCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * When a file is attached to a library item, notify host apps (e.g. for AI auto-tagging).
     */
    protected function registerLibraryFileStoredEvent(): void
    {
        if (! class_exists(Media::class)) {
            return;
        }

        Media::created(function (Media $media): void {
            $model = $media->model;
            $libraryItemModel = FilamentLibraryPlugin::libraryItemModelClass();

            if (! is_object($model) || ! is_a($model, $libraryItemModel)) {
                return;
            }

            /** @var LibraryItem $item */
            $item = $model;

            if ($item->type !== 'file') {
                return;
            }

            event(new LibraryFileStored($item, $media));
        });
    }

    /**
     * When a soft-deleted library file is restored, notify host apps (e.g. for RAG re-ingestion).
     */
    protected function registerLibraryFileRestoredEvent(): void
    {
        $libraryItemModel = FilamentLibraryPlugin::libraryItemModelClass();

        $libraryItemModel::restored(function (LibraryItem $item): void {
            if ($item->type !== 'file') {
                return;
            }

            $media = null;

            if (class_exists(Media::class) && Schema::hasTable('media')) {
                $firstMedia = $item->getFirstMedia();
                $media = $firstMedia instanceof Media ? $firstMedia : null;
            }

            event(new LibraryFileRestored($item, $media));
        });
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_library_items_table',
            'create_library_item_permissions_table',
            'create_library_item_tags_table',
            'create_library_item_favorites_table',
        ];
    }
}
