<?php

namespace Tapp\FilamentLibrary\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Tapp\FilamentLibrary\FilamentLibraryPlugin;
use Tapp\FilamentLibrary\Models\LibraryItem;

class ReviewLibraryItemCommand extends Command
{
    public $signature = 'filament-library:review
        {id : Library item id}
        {action : publish, reject, or return}
        {--reason= : Note stored when rejecting}
        {--user= : Gatekeeper user id}';

    public $description = 'Publish, reject, or return a library item';

    public function handle(): int
    {
        $model = FilamentLibraryPlugin::libraryItemModelClass();
        $item = $model::query()->find($this->argument('id'));

        if (! $item instanceof LibraryItem) {
            $this->error('Library item was not found.');

            return self::FAILURE;
        }

        $gatekeeperId = $this->gatekeeperId();

        if ($gatekeeperId === false) {
            return self::FAILURE;
        }

        $action = strtolower((string) $this->argument('action'));
        $reason = $this->option('reason');
        $reason = is_string($reason) && trim($reason) !== '' ? trim($reason) : null;

        match ($action) {
            'publish' => $item->publish($gatekeeperId),
            'reject' => $item->reject($gatekeeperId, $reason),
            'return' => $item->returnToDraft($gatekeeperId),
            default => null,
        };

        if (! in_array($action, ['publish', 'reject', 'return'], true)) {
            $this->error('Action must be publish, reject, or return.');

            return self::FAILURE;
        }

        $item->refresh();
        $this->info("Library item {$item->getKey()} is now {$item->publication_status->value}.");

        return self::SUCCESS;
    }

    protected function gatekeeperId(): int | false | null
    {
        $userId = $this->option('user');

        if ($userId === null || $userId === '') {
            return null;
        }

        $userId = (int) $userId;

        if ($userId < 1 || ! DB::table('users')->where('id', $userId)->exists()) {
            $this->error('Gatekeeper user was not found.');

            return false;
        }

        return $userId;
    }
}
