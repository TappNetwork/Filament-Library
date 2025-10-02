# Filament Library Permission System

The Filament Library plugin includes a comprehensive permission system that allows you to control access to library items on a per-user basis.

## Features

- **User-based permissions**: Grant view/edit permissions to specific users
- **Google Drive-style inheritance**: Permissions cascade from parent folders to children
- **Bulk permission management**: Assign permissions to multiple users and items at once
- **Cached performance**: Permission checks are cached for optimal performance
- **Configurable user model**: Works with any Laravel user model

## Basic Usage

### 1. Assign Permissions

You can assign permissions to users in several ways:

#### Via the UI
1. Go to the Library Items list
2. Select one or more items
3. Click "Manage Permissions" in the bulk actions
4. Choose users and permission level (view/edit)
5. Optionally cascade permissions to child items

#### Via Code
```php
use Tapp\FilamentLibrary\Services\PermissionService;

$permissionService = app(PermissionService::class);

// Assign view permission to a user
$permissionService->assignPermission($user, $libraryItem, 'view');

// Assign edit permission to a user
$permissionService->assignPermission($user, $libraryItem, 'edit');

// Bulk assign permissions
$permissionService->bulkAssignPermissions($items, [
    'user_ids' => [1, 2, 3],
    'permission' => 'view',
    'cascade_to_children' => true
]);
```

### 2. Check Permissions

```php
// Check if user can view an item
if ($libraryItem->hasPermission($user, 'view')) {
    // User can view this item
}

// Check if user can edit an item
if ($libraryItem->hasPermission($user, 'edit')) {
    // User can edit this item
}
```

## Advanced Configuration

### Custom User Model Integration

If you want to use the `HasLibraryAccess` trait for additional functionality:

```php
// In your User model
use Tapp\FilamentLibrary\Traits\HasLibraryAccess;

class User extends Authenticatable
{
    use HasLibraryAccess;
    
    // Override to add role-based logic
    public function isLibraryAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('library-admin');
    }
}
```

### Permission Inheritance

Permissions automatically inherit from parent folders:

```php
// If a user has 'view' permission on a folder,
// they automatically get 'view' permission on all child items
$folder = LibraryItem::where('name', 'Documents')->first();
$permissionService->assignPermission($user, $folder, 'view');

// Now the user can view all files in the Documents folder
$childFile = $folder->children()->first();
$childFile->hasPermission($user, 'view'); // Returns true
```

### Caching

Permission checks are cached for 1 hour by default. You can clear the cache:

```php
// Clear all permission caches
$permissionService->clearPermissionCache($user);

// Or clear cache for a specific item
$permissionService->clearPermissionCache($user, $libraryItem);
```

## UI Components

### Permissions Column

The library items table includes a toggleable "Permissions" column that shows:
- 👤 Owner (creator of the item)
- 👁️ Users with view permission
- ✏️ Users with edit permission

### Bulk Actions

- **Manage Permissions**: Assign permissions to multiple users across multiple items
- **Cascade to Children**: Automatically apply permissions to all child items

## Security

- All permission checks go through Laravel's authorization system
- Policies ensure consistent permission enforcement
- Fallback implementations work even without the `HasLibraryAccess` trait
- Creator permissions are always respected

## Troubleshooting

### "Method not found" errors

If you see errors like `canViewRootLibraryItems()`, the plugin provides fallback implementations. The permission system will work without requiring the `HasLibraryAccess` trait on your User model.

### Performance issues

If you have many users and items, consider:
- Adjusting the cache TTL in the `PermissionService`
- Using database indexes on the permission table
- Implementing role-based permissions for better performance

## API Reference

### PermissionService Methods

- `assignPermission($user, $item, $permission)` - Assign a permission
- `removePermission($user, $item, $permission)` - Remove a permission
- `hasPermission($user, $item, $permission)` - Check if user has permission
- `bulkAssignPermissions($items, $data)` - Bulk assign permissions
- `cascadePermissionsToChildren($folder, $userIds, $permission)` - Cascade permissions

### LibraryItem Methods

- `hasPermission($user, $permission)` - Check if user has permission on this item
- `permissions()` - Get all permissions for this item
- `getAllPermissions()` - Get all permissions including inherited ones








