<?php

namespace Tapp\FilamentLibrary;

use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tapp\FilamentLibrary\Commands\FilamentLibraryCommand;
use Tapp\FilamentLibrary\Testing\TestsFilamentLibrary;

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

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
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

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/filament-library/{$file->getFilename()}"),
                ], 'filament-library-stubs');
            }
        }

        // Testing
        Testable::mixin(new TestsFilamentLibrary);
    }

    protected function getAssetPackageName(): ?string
    {
        return 'tapp/filament-library';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        $assets = [];

        // Only register assets if they exist
        if (file_exists(__DIR__ . '/../resources/dist/filament-library.css')) {
            $assets[] = Css::make('filament-library-styles', __DIR__ . '/../resources/dist/filament-library.css');
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
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            '2024_01_01_000000_create_library_items_table',
            '2024_01_01_000001_create_library_item_permissions_table',
        ];
    }
}
