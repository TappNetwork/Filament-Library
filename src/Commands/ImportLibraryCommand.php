<?php

namespace Tapp\FilamentLibrary\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Tapp\FilamentLibrary\Enums\LibraryPublicationStatus;
use Tapp\FilamentLibrary\Services\LibraryImporter;

class ImportLibraryCommand extends Command
{
    public $signature = 'filament-library:import
        {path : CSV manifest path}
        {--user= : User id recorded as the creator}
        {--status=pending : draft or pending. Published values are ignored}';

    public $description = 'Import a library manifest as draft or pending items';

    public function handle(LibraryImporter $importer): int
    {
        $userId = (int) $this->option('user');

        if ($userId < 1 || ! DB::table('users')->where('id', $userId)->exists()) {
            $this->error('Pass a valid --user id. Imports are recorded against that user.');

            return self::FAILURE;
        }

        $status = LibraryPublicationStatus::tryFrom(strtolower((string) $this->option('status')));

        if (! in_array($status, [LibraryPublicationStatus::Draft, LibraryPublicationStatus::Pending], true)) {
            $this->error('Status must be draft or pending. Imports are not published automatically.');

            return self::FAILURE;
        }

        $result = $importer->importCsv((string) $this->argument('path'), $userId, $status);

        foreach ($result->errors as $error) {
            $this->error($error);
        }

        $this->info("Imported {$result->importedCount()} library item(s) as {$status->value}.");

        if (! $result->succeeded()) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
