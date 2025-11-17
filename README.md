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

**Note:** By default, users have an `isLibraryAdmin()` method that returns `false`. You can override this in your User model for custom logic.

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
