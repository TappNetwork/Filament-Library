<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tapp\FilamentLibrary\Enums\LibraryPublicationStatus;
use Tapp\FilamentLibrary\Events\LibraryItemPublished;
use Tapp\FilamentLibrary\Events\LibraryItemRejected;
use Tapp\FilamentLibrary\Models\LibraryItem;
use Tapp\FilamentLibrary\Models\LibraryItemTag;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;
use Tapp\FilamentLibrary\Services\LibraryImporter;

function createLibraryUser(): int
{
    return DB::table('users')->insertGetId([
        'name' => 'Librarian',
        'email' => 'librarian-' . uniqid('', true) . '@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('ordinary creates stay published and searchable', function (): void {
    $userId = createLibraryUser();

    $item = LibraryItem::query()->create([
        'name' => 'Existing standard',
        'type' => 'file',
        'created_by' => $userId,
        'updated_by' => $userId,
        'general_access' => 'private',
    ]);

    expect($item->publication_status)->toBe(LibraryPublicationStatus::Published)
        ->and($item->isSearchable())->toBeTrue()
        ->and(LibraryItem::query()->searchable()->pluck('id'))->toContain($item->id);
});

test('new items wait for approval when the host turns that on', function (): void {
    config()->set('filament-library.publication.new_items_require_approval', true);
    $userId = createLibraryUser();

    $item = LibraryItem::query()->create([
        'name' => 'Waiting upload',
        'type' => 'file',
        'created_by' => $userId,
        'updated_by' => $userId,
        'general_access' => 'anyone_can_view',
    ]);

    expect($item->publication_status)->toBe(LibraryPublicationStatus::Pending)
        ->and(LibraryItem::query()->searchable()->pluck('id'))->not->toContain($item->id);
});

test('imports land pending with firm metadata and stay out of search', function (): void {
    Event::fake([LibraryItemPublished::class, LibraryItemRejected::class]);
    $userId = createLibraryUser();

    $item = app(LibraryImporter::class)->importRow([
        'name' => 'Title Block.dwg',
        'firm_path' => '\\s9\concept\title-block.dwg',
        'library_area' => 'Concept',
        'committee' => 'Design',
        'project_tags' => 'pilot|title-block',
        'type' => 'file',
        'publication_status' => 'published',
    ], $userId);

    $item->load('tags', 'parent');

    expect($item->publication_status)->toBe(LibraryPublicationStatus::Pending)
        ->and($item->firm_path)->toBe('\\s9\concept\title-block.dwg')
        ->and($item->library_area)->toBe('Concept')
        ->and($item->committee)->toBe('Design')
        ->and($item->project_tags)->toBe(['pilot', 'title-block'])
        ->and($item->parent?->type)->toBe('folder')
        ->and($item->parent?->name)->toBe('Concept')
        ->and($item->parent?->publication_status)->toBe(LibraryPublicationStatus::Published)
        ->and($item->tags->pluck('name')->sort()->values()->all())->toBe(['pilot', 'title-block'])
        ->and($item->isSearchable())->toBeFalse()
        ->and(LibraryItem::query()->searchable()->pluck('id'))->not->toContain($item->id)
        ->and(LibraryItem::query()->unpublished()->pluck('id'))->toContain($item->id)
        ->and(LibraryItem::query()->withProjectTag('pilot')->pluck('id'))->toContain($item->id)
        ->and(LibraryItem::query()->inLibraryArea('Concept')->pluck('id'))->toContain($item->id)
        ->and(LibraryItem::query()->forCommittee('Design')->pluck('id'))->toContain($item->id);

    Event::assertNotDispatched(LibraryItemPublished::class);
});

test('a manifest can land rows as draft', function (): void {
    $userId = createLibraryUser();

    $item = app(LibraryImporter::class)->importRow([
        'name' => 'Draft detail',
        'publication_status' => 'draft',
    ], $userId, LibraryPublicationStatus::Pending);

    expect($item->publication_status)->toBe(LibraryPublicationStatus::Draft)
        ->and(LibraryItem::query()->searchable()->pluck('id'))->not->toContain($item->id);
});

test('publishing makes an item searchable and rejecting removes it again', function (): void {
    Event::fake([LibraryItemPublished::class, LibraryItemRejected::class]);
    $userId = createLibraryUser();
    $item = app(LibraryImporter::class)->importRow([
        'name' => 'Door schedule',
        'firm_path' => '\\s9\technical\door-schedule.xlsx',
        'library_area' => 'Technical',
        'committee' => 'Technical',
        'project_tags' => ['doors'],
    ], $userId);

    $item->publish($userId);
    $item->refresh();

    expect($item->publication_status)->toBe(LibraryPublicationStatus::Published)
        ->and($item->published_by)->toBe($userId)
        ->and($item->isSearchable())->toBeTrue()
        ->and(LibraryItem::query()->searchable()->pluck('id'))->toContain($item->id);

    Event::assertDispatched(LibraryItemPublished::class);

    $item->publish($userId);
    Event::assertDispatchedTimes(LibraryItemPublished::class, 1);

    $item->reject($userId, 'Needs a companion PDF');
    $item->refresh();

    expect($item->publication_status)->toBe(LibraryPublicationStatus::Rejected)
        ->and($item->rejection_reason)->toBe('Needs a companion PDF')
        ->and($item->published_at)->toBeNull()
        ->and(LibraryItem::query()->searchable()->pluck('id'))->not->toContain($item->id);

    Event::assertDispatched(LibraryItemRejected::class, function (LibraryItemRejected $event) use ($item): bool {
        return $event->libraryItem->is($item) && $event->reason === 'Needs a companion PDF';
    });
});

test('returning a pending item to draft keeps it out of search', function (): void {
    $userId = createLibraryUser();
    $item = app(LibraryImporter::class)->importRow(['name' => 'Returned sheet'], $userId);

    $item->returnToDraft($userId);
    $item->refresh();

    expect($item->publication_status)->toBe(LibraryPublicationStatus::Draft)
        ->and(LibraryItem::query()->searchable()->pluck('id'))->not->toContain($item->id);
});

test('a csv manifest imports valid rows and reports invalid ones', function (): void {
    $userId = createLibraryUser();
    config()->set('filament-library.publication.committees', ['Design', 'Technical']);

    $path = tempnam(sys_get_temp_dir(), 'library-manifest');
    file_put_contents($path, <<<'CSV'
name,firm_path,folder,committee,project_tags,type,status
Concept cover.pdf,\\s9\concept\cover.pdf,Concept,Design,pilot;cover,file,published
,\\s9\missing-name.pdf,Concept,Design,pilot,file,pending
Finance notes.pdf,\\s9\finance\notes.pdf,Concept,Finance,pilot,file,pending
CSV);

    try {
        $result = app(LibraryImporter::class)->importCsv($path, $userId);
    } finally {
        unlink($path);
    }

    expect($result->importedCount())->toBe(1)
        ->and($result->succeeded())->toBeFalse()
        ->and($result->errors)->toHaveCount(2)
        ->and($result->items[0]->publication_status)->toBe(LibraryPublicationStatus::Pending)
        ->and($result->items[0]->library_area)->toBe('Concept')
        ->and($result->items[0]->committee)->toBe('Design')
        ->and($result->items[0]->firm_path)->toBe('\\\\s9\\concept\\cover.pdf')
        ->and($result->items[0]->project_tags)->toBe(['pilot', 'cover'])
        ->and(LibraryItem::query()->searchable()->where('name', 'Concept cover.pdf')->exists())->toBeFalse()
        ->and(LibraryItem::query()->where('name', 'Finance notes.pdf')->exists())->toBeFalse();
});

test('import and review commands publish only through the gatekeeper', function (): void {
    $userId = createLibraryUser();
    $path = tempnam(sys_get_temp_dir(), 'library-manifest');
    file_put_contents($path, <<<'CSV'
name,firm_path,library_area,committee,project_tags
Wall section.pdf,\\s9\technical\wall-section.pdf,Technical,Technical,walls
CSV);

    try {
        $this->artisan('filament-library:import', [
            'path' => $path,
            '--user' => $userId,
            '--status' => 'draft',
        ])->assertSuccessful();
    } finally {
        unlink($path);
    }

    $item = LibraryItem::query()->where('name', 'Wall section.pdf')->first();

    expect($item)->not->toBeNull()
        ->and($item->publication_status)->toBe(LibraryPublicationStatus::Draft)
        ->and(LibraryItem::query()->searchable()->pluck('id'))->not->toContain($item->id);

    $this->artisan('filament-library:review', [
        'id' => $item->id,
        'action' => 'publish',
        '--user' => $userId,
    ])->assertSuccessful();

    $item->refresh();

    expect($item->publication_status)->toBe(LibraryPublicationStatus::Published)
        ->and(LibraryItem::query()->searchable()->pluck('id'))->toContain($item->id);

    $this->artisan('filament-library:review', [
        'id' => $item->id,
        'action' => 'reject',
        '--user' => $userId,
        '--reason' => 'Hold for committee',
    ])->assertSuccessful();

    $item->refresh();

    expect($item->publication_status)->toBe(LibraryPublicationStatus::Rejected)
        ->and(LibraryItem::query()->searchable()->pluck('id'))->not->toContain($item->id);
});

test('import refuses a published status flag', function (): void {
    $userId = createLibraryUser();
    $path = tempnam(sys_get_temp_dir(), 'library-manifest');
    file_put_contents($path, "name\nShould not import\n");

    try {
        $this->artisan('filament-library:import', [
            'path' => $path,
            '--user' => $userId,
            '--status' => 'published',
        ])->assertFailed();
    } finally {
        unlink($path);
    }

    expect(LibraryItem::query()->where('name', 'Should not import')->exists())->toBeFalse();
});

test('renaming an item keeps project tags that were stored on import', function (): void {
    $userId = createLibraryUser();
    $item = app(LibraryImporter::class)->importRow([
        'name' => 'Tagged block',
        'project_tags' => 'alpha, beta',
    ], $userId);

    $item->update(['name' => 'Tagged block renamed']);
    $item->refresh()->load('tags');

    expect($item->project_tags)->toBe(['alpha', 'beta'])
        ->and($item->tags->pluck('name')->sort()->values()->all())->toBe(['alpha', 'beta'])
        ->and(LibraryItemTag::query()->whereIn('name', ['alpha', 'beta'])->count())->toBe(2);
});

test('firm metadata fields are available on the library form', function (): void {
    $names = array_map(
        fn ($component) => $component->getName(),
        LibraryItemResource::firmMetadataComponents(),
    );

    expect($names)->toBe(['firm_path', 'library_area', 'committee', 'project_tags']);
});
