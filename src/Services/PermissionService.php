<?php

namespace Tapp\FilamentLibrary\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Tapp\FilamentLibrary\Models\LibraryItem;
use Tapp\FilamentLibrary\Models\LibraryItemPermission;

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
        LibraryItemPermission::updateOrCreate(
            [
                'library_item_id' => $item->id,
                'user_id' => $user->id,
                'permission' => $permission,
            ],
            [
                'library_item_id' => $item->id,
                'user_id' => $user->id,
                'permission' => $permission,
            ]
        );

        $this->clearPermissionCache($user->id, $item->id);
    }

    /**
     * Remove permissions from a user for an item.
     */
    public function removePermission($user, LibraryItem $item, string $permission): void
    {
        LibraryItemPermission::where([
            'library_item_id' => $item->id,
            'user_id' => $user->id,
            'permission' => $permission,
        ])->delete();

        $this->clearPermissionCache($user->id, $item->id);
    }

    /**
     * Bulk assign permissions to multiple users for multiple items.
     */
    public function bulkAssignPermissions(Collection $items, array $data): void
    {
        $userIds = $data['user_ids'] ?? [];
        $permission = $data['permission'] ?? 'view';
        $cascadeToChildren = $data['cascade_to_children'] ?? false;

        foreach ($items as $item) {
            foreach ($userIds as $userId) {
                $this->assignPermission(
                    \App\Models\User::find($userId),
                    $item,
                    $permission
                );
            }

            // Cascade permissions to children if requested
            if ($cascadeToChildren && $item->type === 'folder') {
                $this->cascadePermissionsToChildren($item, $userIds, $permission);
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
                $this->assignPermission(
                    \App\Models\User::find($userId),
                    $child,
                    $permission
                );
            }

            // Recursively cascade to grandchildren
            if ($child->type === 'folder') {
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
        // Check direct permissions
        $directPermission = $item->permissions()
            ->where('user_id', $user->id)
            ->where('permission', $permission)
            ->exists();

        if ($directPermission) {
            return true;
        }

        // Check inherited permissions from parent folders
        if ($item->parent_id) {
            return $this->checkPermissionRecursive($user, $item->parent, $permission);
        }

        return false;
    }

    /**
     * Generate cache key for permission check.
     */
    private function getCacheKey(int $userId, int $itemId, string $permission): string
    {
        return self::CACHE_PREFIX . $userId . '_' . $itemId . '_' . $permission;
    }
}
