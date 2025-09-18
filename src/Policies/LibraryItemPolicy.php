<?php

namespace Tapp\FilamentLibrary\Policies;

use Tapp\FilamentLibrary\Models\LibraryItem;
use Illuminate\Foundation\Auth\User;

class LibraryItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->canViewRootLibraryItems();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LibraryItem $libraryItem): bool
    {
        return $user->canViewLibraryItem($libraryItem);
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
        return $user->canEditLibraryItem($libraryItem);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LibraryItem $libraryItem): bool
    {
        return $user->canEditLibraryItem($libraryItem);
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
        return $user->canEditLibraryItem($libraryItem);
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
        return $user->canEditLibraryItem($libraryItem);
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
        return $user->canEditLibraryItem($libraryItem);
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
        return $user->canEditLibraryItem($libraryItem);
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
