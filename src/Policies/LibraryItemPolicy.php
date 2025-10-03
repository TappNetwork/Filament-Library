<?php

namespace Tapp\FilamentLibrary\Policies;

use Illuminate\Foundation\Auth\User;
use Tapp\FilamentLibrary\Models\LibraryItem;

class LibraryItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Check if user is library admin
        if (method_exists($user, 'isLibraryAdmin') && $user->isLibraryAdmin()) {
            return true;
        }

        // Allow all users to view (can be restricted by individual item permissions)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LibraryItem $libraryItem): bool
    {
        // Check if user is library admin
        if (method_exists($user, 'isLibraryAdmin') && $user->isLibraryAdmin()) {
            return true;
        }

        // Check if user is creator or has permission
        return $libraryItem->created_by === $user->id || $libraryItem->hasPermission($user, 'view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Users can create items if they can view the parent folder
        // This will be checked in the resource/form level
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LibraryItem $libraryItem): bool
    {
        // Check if user is library admin
        if (method_exists($user, 'isLibraryAdmin') && $user->isLibraryAdmin()) {
            return true;
        }

        // Check if user is creator or has permission
        return $libraryItem->created_by === $user->id || $libraryItem->hasPermission($user, 'edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LibraryItem $libraryItem): bool
    {
        // Check if user is library admin
        if (method_exists($user, 'isLibraryAdmin') && $user->isLibraryAdmin()) {
            return true;
        }

        // Only creators and owners can delete items
        return $libraryItem->created_by === $user->id || $libraryItem->hasPermission($user, 'delete');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        // This will be checked per item in the bulk action
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LibraryItem $libraryItem): bool
    {
        // Check if user is library admin
        if (method_exists($user, 'isLibraryAdmin') && $user->isLibraryAdmin()) {
            return true;
        }

        // Only creators and owners can permanently delete items
        return $libraryItem->created_by === $user->id || $libraryItem->hasPermission($user, 'delete');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        // This will be checked per item in the bulk action
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LibraryItem $libraryItem): bool
    {
        // Check if user is library admin
        if (method_exists($user, 'isLibraryAdmin') && $user->isLibraryAdmin()) {
            return true;
        }

        // Check if user is creator or has permission
        return $libraryItem->created_by === $user->id || $libraryItem->hasPermission($user, 'edit');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        // This will be checked per item in the bulk action
        return true;
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, LibraryItem $libraryItem): bool
    {
        // Check if user is library admin
        if (method_exists($user, 'isLibraryAdmin') && $user->isLibraryAdmin()) {
            return true;
        }

        // Check if user is creator or has permission
        return $libraryItem->created_by === $user->id || $libraryItem->hasPermission($user, 'edit');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return false; // Not implemented yet
    }

    /**
     * Determine whether the user can manage permissions.
     */
    public function managePermissions(User $user, LibraryItem $libraryItem): bool
    {
        // Check if user is library admin
        if (method_exists($user, 'isLibraryAdmin') && $user->isLibraryAdmin()) {
            return true;
        }

        // Check if user is creator or has permission
        return $libraryItem->created_by === $user->id || $libraryItem->hasPermission($user, 'edit');
    }

    /**
     * Determine whether the user can bulk manage permissions.
     */
    public function bulkManagePermissions(User $user): bool
    {
        // This will be checked per item in the bulk action
        return true;
    }
}
