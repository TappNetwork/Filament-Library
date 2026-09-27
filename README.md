# Filament Library Plugin

A comprehensive file and document management system for Filament applications, featuring Google Drive-style permissions, automatic inheritance, and flexible access controls.

## Features

- **📁 File & Folder Management** - Upload files, create folders, and organize content
- **🔗 External Links** - Add and manage external links with descriptions (including video embeds)
- **👥 Advanced Permissions** - Google Drive-style ownership with Creator, Owner, Editor, and Viewer roles
- **🔄 Automatic Inheritance** - Permissions automatically inherit from parent folders
- **🔍 Multiple Views** - Public Library, My Documents, Shared with Me, Created by Me, Favorites, and Search All
- **🏷️ Tags & Favorites** - Organize items with tags and mark favorites for quick access
- **⚙️ Configurable Admin Access** - Flexible admin role configuration
- **🎨 Filament Integration** - Native Filament UI components and navigation
- **🏢 Multi-Tenancy Support** - Optional team/organization scoping for all library content

## Installation

You can install the package via composer:

```bash
composer require tapp/filament-library
```

### Database Setup

The package will automatically publish and run migrations. You'll need to add the `LibraryUser` trait to your User model:

```php
// app/Models/User.php
use Tapp\FilamentLibrary\Traits\LibraryUser;

class User extends Authenticatable
{
    use LibraryUser;
    // ... other traits and methods
}
```

> [!WARNING]  
> If you are using multi-tenancy please see the "Multi-Tenancy Support" instructions below **before** publishing and running migrations.

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-library-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-library-config"
```

## Basic Usage

### 1. Add to Filament Panel

```php
use Tapp\FilamentLibrary\FilamentLibraryPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentLibraryPlugin::make(),
        ]);
}
```

### 2. Configure Admin Access

```php
// In your AppServiceProvider
use Tapp\FilamentLibrary\FilamentLibraryPlugin;

public function boot()
{
    // Option 1: Use different role name
    FilamentLibraryPlugin::setLibraryAdminCallback(function ($user) {
        return $user->hasRole('super-admin');
    });
    
    // Option 2: Custom logic
    FilamentLibraryPlugin::setLibraryAdminCallback(function ($user) {
        return $user->is_superuser || $user->hasRole('library-manager');
    });
}
```

### 3. Navigation

The plugin automatically adds navigation items under "Resource Library":
- **Library** - Main library view
- **Search All** - Search across all accessible content
- **My Documents** - Personal documents and folders
- **Shared with Me** - Items shared by other users
- **Created by Me** - Items you created
- **Favorites** - Items you've marked as favorites

To hide those links for some users (for example roles that may enter the panel but should not see the library), use `navigationVisibleUsing()`:

```php
use Tapp\FilamentLibrary\FilamentLibraryPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentLibraryPlugin::make()
                ->navigationVisibleUsing(fn (): bool => auth()->user()?->can('view-any library-item') ?? false),
        ]);
}
```

When unset, navigation items remain visible (backward compatible). Direct library URLs are still authorized by your library item policies.

## Permissions System

The plugin features a sophisticated permissions system inspired by Google Drive.

### Quick Overview

- **Creator** - Permanent, always has access, cannot be changed
- **Owner** - Manages sharing, can be transferred, has full permissions
- **Editor** - Can view and edit content, cannot manage sharing
- **Viewer** - Can only view content

### Automatic Permissions

- **Personal Folders** - Automatically created for new users
- **Permission Inheritance** - Child items inherit parent folder permissions
- **Admin Override** - Library admins can access all content

## Multi-Tenancy Support

Filament Library includes built-in support for multi-tenancy, allowing you to scope library items, permissions, and tags to specific tenants (e.g., teams, organizations, workspaces).

### ⚠️ Important: Enable Tenancy Before Migrations

**You MUST configure and enable tenancy in the config file BEFORE running the migrations.** The migrations check the tenancy configuration to determine whether to add tenant columns to the database tables. If you enable tenancy after running migrations, you'll need to manually add the tenant columns to your database.

### Quick Setup

1. **Configure your Filament panel with tenancy** (see [Filament Tenancy docs](https://filamentphp.com/docs/4.x/users/tenancy))
2. **Publish the config file**:
   ```bash
   php artisan vendor:publish --tag="filament-library-config"
   ```
3. **Enable tenancy in `config/filament-library.php`**:
   ```php
   'tenancy' => [
       'enabled' => true, // ⚠️ Set this BEFORE running migrations!
       'model' => \App\Models\Team::class,
   ],
   ```
4. **Run migrations**:
   ```bash
   php artisan migrate
   ```

For complete setup instructions, troubleshooting, and advanced configuration, see [TENANCY.md](TENANCY.md).

## Configuration

The config file (`config/filament-library.php`) includes the following options:

### User Model

```php
'user_model' => env('FILAMENT_LIBRARY_USER_MODEL', 'App\\Models\\User'),
```

Specify the user model for the application.

### Video Link Support (Optional)

The library supports video links from various platforms. To customize supported domains, add this to your config:

```php
'video' => [
    'supported_domains' => [
        'youtube.com',
        'youtu.be',
        'vimeo.com',
        'wistia.com',
    ],
],
```

### Secure File URLs (Optional)

Configure how long temporary download URLs remain valid:

```php
'url' => [
    'temporary_expiration_minutes' => 60, // Default: 60 minutes
],
```

### Admin Access Configuration (Optional)

To configure which users can access admin features, add this to your config:

```php
'admin_role' => 'Admin', // Role name to check
'admin_callback' => null, // Custom callback function
```

Or set it programmatically in your `AppServiceProvider`:

```php
use Tapp\FilamentLibrary\FilamentLibraryPlugin;

public function boot()
{
    FilamentLibraryPlugin::setLibraryAdminCallback(function ($user) {
        return $user->hasRole('super-admin');
    });
}
```

## Events

The package dispatches events your application can listen for to extend behavior (for example, search indexing, webhooks after uploads, ...).

### `LibraryFileStored`

[`Tapp\FilamentLibrary\Events\LibraryFileStored`](src/Events/LibraryFileStored.php) is fired after a new file is stored on a library item: when Spatie Media Library creates a `Media` record for a `LibraryItem` whose `type` is `file`.

The event exposes:

- `$libraryItem` — the [`LibraryItem`](src/Models/LibraryItem.php) the file was attached to
- `$media` — the new [`Media`](https://github.com/spatie/laravel-medialibrary) model instance, or `null` if not available in edge cases

Example listener registration in `AppServiceProvider`:

```php
use Illuminate\Support\Facades\Event;
use Tapp\FilamentLibrary\Events\LibraryFileStored;

public function boot(): void
{
    Event::listen(LibraryFileStored::class, function (LibraryFileStored $event): void {
        // e.g. dispatch a job here
    });
}
```

### `LibraryItemPublished`

[`Tapp\FilamentLibrary\Events\LibraryItemPublished`](src/Events/LibraryItemPublished.php) is fired when a gatekeeper publishes an item. Listen here to update a search index. Imports do not fire this event.

### `LibraryItemRejected`

[`Tapp\FilamentLibrary\Events\LibraryItemRejected`](src/Events/LibraryItemRejected.php) is fired when a gatekeeper rejects an item. The item stays out of search.

### `LibraryFileRestored`

[`Tapp\FilamentLibrary\Events\LibraryFileRestored`](src/Events/LibraryFileRestored.php) is fired after a soft-deleted `LibraryItem` of type `file` is restored (for example via Filament's Restore action).

The event exposes:

- `$libraryItem` — the restored [`LibraryItem`](src/Models/LibraryItem.php)
- `$media` — the item's first [`Media`](https://github.com/spatie/laravel-medialibrary) record, or `null` if none is attached

Example listener registration in `AppServiceProvider`:

```php
use Illuminate\Support\Facades\Event;
use Tapp\FilamentLibrary\Events\LibraryFileRestored;

public function boot(): void
{
    Event::listen(LibraryFileRestored::class, function (LibraryFileRestored $event): void {
        // e.g. re-index or re-ingest RAG chunks here
    });
}
```

## Publication and firm metadata

Library items can carry the path to the real file and the metadata that arrives with a manifest:

- `firm_path` — path to the real file (Copy Path reads this)
- `library_area` — folder or Library area
- `committee` — committee that owns the item
- `project_tags` — project tags from the manifest

Imports do not publish themselves. `php artisan filament-library:import {manifest.csv} --user={id}` creates each row as `pending` (or `--status=draft`). A `published` value in the file is ignored. Draft, pending, and rejected items are excluded from Search All and from the main Library and Public Library lists. `LibraryItem::query()->searchable()` is the same rule for Ask or any other search.

A gatekeeper publishes or rejects an item with `php artisan filament-library:review {id} publish` or `reject`, or from the Gatekeeper Queue when the library admin check passes. Publishing dispatches `LibraryItemPublished`. Rejecting dispatches `LibraryItemRejected` and keeps the item out of search. `return` sends a pending item back to draft, which is also excluded from search.

Ordinary creates stay published so existing libraries keep working. Set `publication.new_items_require_approval` to `true` when new items should wait for a gatekeeper too. Set `publication.committees` to the allowed committee names (for example `['Design', 'Technical']`) when imports should reject any other committee. Leave it empty to accept any committee name.

Manifest columns: `name`, `firm_path`, `library_area` (or `folder`), `committee`, `project_tags` (split on `|`, `;`, or `,`), `type` (`file`, `folder`, or `link`), `url`, and `status` (`draft` or `pending`).

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
