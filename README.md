# Filament Library Plugin

A comprehensive file and document management system for Filament applications, featuring Google Drive-style permissions, automatic inheritance, and flexible access controls.

## Features

- **📁 File & Folder Management** - Upload files, create folders, and organize content
- **🔗 External Links** - Add and manage external links with descriptions
- **👥 Advanced Permissions** - Google Drive-style ownership with Creator, Owner, Editor, and Viewer roles
- **🔄 Automatic Inheritance** - Permissions automatically inherit from parent folders
- **🔍 Multiple Views** - Public Library, My Documents, Shared with Me, Created by Me, and Search All
- **⚙️ Configurable Admin Access** - Flexible admin role configuration
- **🎨 Filament Integration** - Native Filament UI components and navigation

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

The plugin automatically adds navigation items:
- **Library** - Main library view
- **Search All** - Search across all accessible content
- **My Documents** - Personal documents and folders
- **Shared with Me** - Items shared by other users
- **Created by Me** - Items you created

## Permissions System

The plugin features a sophisticated permissions system inspired by Google Drive. See [Permissions Documentation](docs/permissions.md) for complete details.

### Quick Overview

- **Creator** - Permanent, always has access, cannot be changed
- **Owner** - Manages sharing, can be transferred, has full permissions
- **Editor** - Can view and edit content, cannot manage sharing
- **Viewer** - Can only view content

### Automatic Permissions

- **Personal Folders** - Automatically created for new users
- **Permission Inheritance** - Child items inherit parent folder permissions
- **Admin Override** - Library admins can access all content

## Configuration

### Admin Role Configuration

```php
// config/filament-library.php
return [
    'admin_role' => 'Admin', // Default admin role
    'admin_callback' => null, // Custom callback function
];
```

### Environment Variables

```env
LIBRARY_ADMIN_ROLE=super-admin
```

## Documentation

- [Permissions System](docs/permissions.md) - Complete permissions guide
- [Customization Guide](docs/customization.md) - Customizing admin access
- [API Reference](docs/api.md) - Developer documentation

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
