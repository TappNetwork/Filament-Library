<?php

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tapp\FilamentLibrary\FilamentLibraryPlugin;
use Tapp\FilamentLibrary\Models\LibraryItem;
use Tapp\FilamentLibrary\Models\LibraryItemPermission;
use Tapp\FilamentLibrary\Resources\Pages\EditFolder;
use Tapp\FilamentLibrary\Resources\RelationManagers\LibraryItemPermissionsRelationManager;

afterEach(function (): void {
    FilamentLibraryPlugin::setLibraryAdminCallback(null);
    Auth::logout();
});

test('permissions table search matches a user first name, last name, and email', function (): void {
    Schema::table('users', function (Blueprint $table): void {
        $table->string('first_name')->nullable();
        $table->string('last_name')->nullable();
    });

    $owner = permissionsSearchUser('Owner', 'owner@example.com');
    $rachel = permissionsSearchUser('rnuser', 'rn@example.com', 'Rachel', 'Nguyen');
    $chris = permissionsSearchUser('causer', 'ca@example.com', 'Chris', 'Alvarez');
    $pat = permissionsSearchUser('pruser', 'pr@example.com', 'Pat', 'Rachelson');

    $folder = permissionsSearchFolder($owner, 'Shared folder');
    $otherFolder = permissionsSearchFolder($owner, 'Other folder');

    $rachelPermission = permissionsSearchGrant($folder, $rachel, 'viewer');
    $chrisPermission = permissionsSearchGrant($folder, $chris, 'editor');
    $patPermission = permissionsSearchGrant($folder, $pat, 'viewer');
    $otherFolderPermission = permissionsSearchGrant($otherFolder, $rachel, 'owner');

    $component = permissionsSearchTable($owner, $folder);

    $component->searchTable('rachel');

    $sql = str_replace('`', '"', $component->instance()->getFilteredTableQuery()->toSql());
    $usersSubquery = strpos($sql, 'from "users"');
    $firstNameConstraint = strpos($sql, '"first_name"');

    expect($usersSubquery)->toBeInt()
        ->and($firstNameConstraint)->toBeInt()
        ->and($firstNameConstraint)->toBeGreaterThan($usersSubquery);

    $component
        ->searchTable('rachel')
        ->assertCanSeeTableRecords([$rachelPermission, $patPermission])
        ->assertCanNotSeeTableRecords([$chrisPermission, $otherFolderPermission]);

    $component
        ->searchTable('Alvarez')
        ->assertCanSeeTableRecords([$chrisPermission])
        ->assertCanNotSeeTableRecords([$rachelPermission, $patPermission, $otherFolderPermission]);

    $component
        ->searchTable('rn@example.com')
        ->assertCanSeeTableRecords([$rachelPermission])
        ->assertCanNotSeeTableRecords([$chrisPermission, $patPermission, $otherFolderPermission]);
});

test('permissions table search uses name and email when users have no first or last name columns', function (): void {
    expect(Schema::hasColumn('users', 'first_name'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'last_name'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'name'))->toBeTrue();

    $owner = permissionsSearchUser('Owner', 'owner-name-only@example.com');
    $rachel = permissionsSearchUser('Rachel Nguyen', 'rn-name@example.com');
    $chris = permissionsSearchUser('Chris Alvarez', 'ca-name@example.com');

    $folder = permissionsSearchFolder($owner, 'Name only folder');
    $rachelPermission = permissionsSearchGrant($folder, $rachel, 'viewer');
    $chrisPermission = permissionsSearchGrant($folder, $chris, 'editor');

    $component = permissionsSearchTable($owner, $folder);

    $component->searchTable('Rachel');

    $sql = str_replace('`', '"', $component->instance()->getFilteredTableQuery()->toSql());
    $usersSubquery = strpos($sql, 'from "users"');
    $nameConstraint = strpos($sql, '"name"');

    expect($usersSubquery)->toBeInt()
        ->and($nameConstraint)->toBeInt()
        ->and($nameConstraint)->toBeGreaterThan($usersSubquery);

    $component
        ->searchTable('Rachel')
        ->assertCanSeeTableRecords([$rachelPermission])
        ->assertCanNotSeeTableRecords([$chrisPermission]);

    $component
        ->searchTable('rn-name@example.com')
        ->assertCanSeeTableRecords([$rachelPermission])
        ->assertCanNotSeeTableRecords([$chrisPermission]);
});

function permissionsSearchTable(PermissionsRelationSearchUser $owner, LibraryItem $folder): Testable
{
    config()->set('filament-library.user_model', PermissionsRelationSearchUser::class);
    config()->set('auth.providers.users.model', PermissionsRelationSearchUser::class);
    FilamentLibraryPlugin::setLibraryAdminCallback(fn (): bool => true);

    $panel = Panel::make()
        ->id('admin')
        ->default();
    Filament::registerPanel($panel);
    Filament::setCurrentPanel($panel);

    Auth::login($owner);

    return Livewire::actingAs($owner)
        ->test(LibraryItemPermissionsRelationManager::class, [
            'ownerRecord' => $folder,
            'pageClass' => EditFolder::class,
        ]);
}

function permissionsSearchFolder(PermissionsRelationSearchUser $owner, string $name): LibraryItem
{
    return LibraryItem::query()->create([
        'name' => $name,
        'type' => 'folder',
        'created_by' => $owner->id,
        'updated_by' => $owner->id,
        'general_access' => 'private',
    ]);
}

function permissionsSearchGrant(LibraryItem $folder, PermissionsRelationSearchUser $user, string $role): LibraryItemPermission
{
    return $folder->permissions()->create([
        'user_id' => $user->id,
        'role' => $role,
    ]);
}

function permissionsSearchUser(string $name, string $email, ?string $firstName = null, ?string $lastName = null): PermissionsRelationSearchUser
{
    $attributes = [
        'name' => $name,
        'email' => $email,
    ];

    if ($firstName !== null) {
        $attributes['first_name'] = $firstName;
        $attributes['last_name'] = $lastName;
    }

    return PermissionsRelationSearchUser::query()->create($attributes);
}

class PermissionsRelationSearchUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];
}
