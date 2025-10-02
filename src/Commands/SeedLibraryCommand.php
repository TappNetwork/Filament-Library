<?php

namespace Tapp\FilamentLibrary\Commands;

use Illuminate\Console\Command;
use Tapp\FilamentLibrary\Database\Seeders\LibrarySeeder;

class SeedLibraryCommand extends Command
{
    public $signature = 'filament-library:seed {--force : Force the operation without confirmation}';

    public $description = 'Seed the library with sample data';

    public function handle(): int
    {
        if (! $this->option('force')) {
            if (! $this->confirm('This will clear all existing library data and create sample data. Continue?')) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        $this->info('Seeding library with sample data...');

        $seeder = new LibrarySeeder;
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('Library seeded successfully!');

        return self::SUCCESS;
    }
}




