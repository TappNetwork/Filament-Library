<?php

namespace Tapp\FilamentLibrary\Services;

use Illuminate\Support\Facades\Cache;
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

        \Tapp\FilamentLibrary\Models\LibraryItemPermission::updateOrCreate(
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
        \Tapp\FilamentLibrary\Models\LibraryItemPermission::where([
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
        $generalAccess = $data['general_access'] ?? 'private';

        foreach ($items as $item) {
            if (! $item instanceof LibraryItem) {
                continue;
            }

            // Update the general access level for the item
            $item->update(['general_access' => $generalAccess]);

            // Assign permissions to users
            foreach ($userIds as $userId) {
                $userModel = $this->getUserModel();
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
            if (! $child instanceof LibraryItem) {
                continue;
            }

            foreach ($userIds as $userId) {
                $userModel = $this->getUserModel();
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
    public function getUsersWithPermissions(LibraryItem $item): \Illuminate\Support\Collection
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
