<?php

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tapp\FilamentLibrary\FilamentLibraryPlugin;
use Tapp\FilamentLibrary\Models\LibraryItem;
use Tapp\FilamentLibrary\Services\PermissionService;

afterEach(function (): void {
    FilamentLibraryPlugin::setLibraryAdminCallback(null);
    Auth::logout();
});

function createLibraryTestUser(string $email): LibraryPermissionTestUser
{
    $id = DB::table('users')->insertGetId([
        'name' => 'Test User',
        'email' => $email,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = new LibraryPermissionTestUser;
    $user->id = $id;
    $user->name = 'Test User';
    $user->email = $email;
    $user->exists = true;

    return $user;
}

function createPrivateLibraryItem(int $createdBy): LibraryItem
{
    return LibraryItem::query()->create([
        'name' => 'Shared doc',
        'slug' => 'shared-doc-' . uniqid('', true),
        'type' => 'file',
        'created_by' => $createdBy,
        'updated_by' => $createdBy,
        'general_access' => 'private',
    ]);
}

test('bulk assign permissions does not change general access for non-admins', function (): void {
    config()->set('auth.providers.users.model', LibraryPermissionTestUser::class);

    $owner = createLibraryTestUser('owner@example.com');
    $viewer = createLibraryTestUser('viewer@example.com');
    $item = createPrivateLibraryItem($owner->id);

    Auth::login($owner);
    FilamentLibraryPlugin::setLibraryAdminCallback(fn ($user): bool => false);

    app(PermissionService::class)->bulkAssignPermissions([$item], [
        'user_ids' => [$viewer->id],
        'permission' => 'view',
        'general_access' => 'anyone_can_view',
    ]);

    expect($item->fresh()->general_access)->toBe('private')
        ->and($item->fresh()->permissions()->where('user_id', $viewer->id)->exists())->toBeTrue();
});

test('bulk assign permissions allows library admins to change general access', function (): void {
    config()->set('auth.providers.users.model', LibraryPermissionTestUser::class);

    $admin = createLibraryTestUser('admin@example.com');
    $viewer = createLibraryTestUser('viewer-admin@example.com');
    $item = createPrivateLibraryItem($admin->id);

    Auth::login($admin);
    FilamentLibraryPlugin::setLibraryAdminCallback(
        fn ($user): bool => $user && (int) $user->id === (int) $admin->id
    );

    app(PermissionService::class)->bulkAssignPermissions([$item], [
        'user_ids' => [$viewer->id],
        'permission' => 'view',
        'general_access' => 'anyone_can_view',
    ]);

    expect($item->fresh()->general_access)->toBe('anyone_can_view')
        ->and($item->fresh()->permissions()->where('user_id', $viewer->id)->exists())->toBeTrue();
});

test('bulk assign permissions skips general access update when key is omitted', function (): void {
    config()->set('auth.providers.users.model', LibraryPermissionTestUser::class);

    $admin = createLibraryTestUser('admin-omit@example.com');
    $viewer = createLibraryTestUser('viewer-omit@example.com');
    $item = createPrivateLibraryItem($admin->id);

    Auth::login($admin);
    FilamentLibraryPlugin::setLibraryAdminCallback(
        fn ($user): bool => $user && (int) $user->id === (int) $admin->id
    );

    app(PermissionService::class)->bulkAssignPermissions([$item], [
        'user_ids' => [$viewer->id],
        'permission' => 'view',
    ]);

    expect($item->fresh()->general_access)->toBe('private');
});

final class LibraryPermissionTestUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];
}
