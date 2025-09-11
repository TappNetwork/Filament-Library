<?php

namespace Tapp\FilamentLibrary\Commands;

use Illuminate\Console\Command;

class FilamentLibraryCommand extends Command
{
    public $signature = 'filament-library:info';

    public $description = 'Display information about the Filament Library plugin';

    public function handle(): int
    {
        $this->info('Filament Library Plugin');
        $this->line('A Google Drive-like file management system for Filament');
        $this->line('');
        $this->line('Version: 1.0.0');
        $this->line('Author: Tapp Network');
        $this->line('Repository: https://github.com/TappNetwork/Filament-Library');

        return self::SUCCESS;
    }
}
