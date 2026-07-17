<?php

namespace Tapp\FilamentLibrary\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Tapp\FilamentLibrary\FilamentLibraryPlugin;
use Tapp\FilamentLibrary\Models\LibraryItem;

class PermissionService
{
    /**
     * Cache key prefix for permission checks.
     */
    private const CACHE_PREFIX = 'library_permissions_';

    /**
     * Cache TTL in seconds (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Get the user model class.
     */
    protected function getUserModel(): string
    {
        return config('auth.providers.users.model', 'App\\Models\\User');
    }

    /**
     * Check if a user has a specific permission on an item.
     */
    public function hasPermission($user, LibraryItem $item, string $permission): bool
    {
        $cacheKey = $this->getCacheKey($user->id, $item->id, $permission);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $item, $permission) {
            return $this->checkPermissionRecursive($user, $item, $permission);
        });
    }

    /**
     * Assign permissions to a user for an item.
     */
    public function assignPermission($user, LibraryItem $item, string $permission): void
    {
        // Map permission to role
        $role = match ($permission) {
            'view' => 'viewer',
            'edit' => 'editor',
            'owner' => 'owner',
            default => 'viewer',
        };

        FilamentLibraryPlugin::libraryItemPermissionModelClass()::updateOrCreate(
            [
                'library_item_id' => $item->id,
                'user_id' => $user->id,
            ],
            [
                'library_item_id' => $item->id,
                'user_id' => $user->id,
                'role' => $role,
            ]
        );

        $this->clearPermissionCache($user->id, $item->id);
    }

    /**
     * Remove permissions from a user for an item.
     */
    public function removePermission($user, LibraryItem $item, string $permission): void
    {
        FilamentLibraryPlugin::libraryItemPermissionModelClass()::where([
            'library_item_id' => $item->id,
            'user_id' => $user->id,
        ])->delete();

        $this->clearPermissionCache($user->id, $item->id);
    }

    /**
     * Bulk assign permissions to multiple users for multiple items.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, LibraryItem>|array<LibraryItem>  $items
     */
    public function bulkAssignPermissions($items, array $data): void
    {
        $userIds = $data['user_ids'] ?? [];
        $permission = $data['permission'] ?? 'view';
        $canManageGeneralAccess = FilamentLibraryPlugin::isLibraryAdmin(auth()->user());
        $generalAccess = $canManageGeneralAccess && array_key_exists('general_access', $data)
            ? $data['general_access']
            : null;

        foreach ($items as $item) {
            // Only library admins may change general access (e.g. anyone_can_view).
            if ($generalAccess !== null) {
                $item->update(['general_access' => $generalAccess]);
            }

            // Assign permissions to users
            foreach ($userIds as $userId) {
                $userModel = $this->getUserModel();
                /** @var Model $user */
                $user = $userModel::find($userId);
                if ($user) {
                    $this->assignPermission(
                        $user,
                        $item,
                        $permission
                    );
                }
            }
        }
    }

    /**
     * Cascade permissions from a folder to all its children.
     */
    public function cascadePermissionsToChildren(LibraryItem $folder, array $userIds, string $permission): void
    {
        $children = $folder->children;

        foreach ($children as $child) {
            foreach ($userIds as $userId) {
                $userModel = $this->getUserModel();
                /** @var Model $user */
                $user = $userModel::find($userId);
                if ($user) {
                    $this->assignPermission(
                        $user,
                        $child,
                        $permission
                    );
                }
            }

            // Recursively cascade to grandchildren
            if (isset($child->type) && $child->type === 'folder') {
                $this->cascadePermissionsToChildren($child, $userIds, $permission);
            }
        }
    }

    /**
     * Get all users who have permissions on an item.
     */
    public function getUsersWithPermissions(LibraryItem $item): Collection
    {
        return $item->permissions()
            ->with('user')
            ->get()
            ->pluck('user')
            ->unique('id');
    }

    /**
     * Get all permissions for a user on an item.
     */
    public function getUserPermissions($user, LibraryItem $item): array
    {
        $permissions = [];

        // Check direct permissions
        $directPermissions = $item->permissions()
            ->where('user_id', $user->id)
            ->pluck('permission')
            ->toArray();

        $permissions = array_merge($permissions, $directPermissions);

        // Check inherited permissions from parent folders
        if ($item->parent_id) {
            $inheritedPermissions = $this->getUserPermissions($user, $item->parent);
            $permissions = array_merge($permissions, $inheritedPermissions);
        }

        return array_unique($permissions);
    }

    /**
     * Clear permission cache for a user and item.
     */
    public function clearPermissionCache(int $userId, int $itemId): void
    {
        $patterns = [
            self::CACHE_PREFIX . $userId . '_' . $itemId . '_*',
        ];

        foreach ($patterns as $pattern) {
            // Note: This is a simplified cache clearing approach
            // In production, you might want to use a more sophisticated cache tagging system
            Cache::forget($pattern);
        }
    }

    /**
     * Clear all permission cache.
     */
    public function clearAllPermissionCache(): void
    {
        // Note: This is a simplified approach
        // In production, you might want to use cache tags
        Cache::flush();
    }

    /**
     * Check permission recursively (without cache).
     */
    private function checkPermissionRecursive($user, LibraryItem $item, string $permission): bool
    {
        // Use the new effective role logic from the LibraryItem model
        return $item->hasPermission($user, $permission);
    }

    /**
     * Generate cache key for permission check.
     */
    private function getCacheKey(int $userId, int $itemId, string $permission): string
    {
        return self::CACHE_PREFIX . $userId . '_' . $itemId . '_' . $permission;
    }
}
