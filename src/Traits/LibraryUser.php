<?php

namespace Tapp\FilamentLibrary\Traits;

use Tapp\FilamentLibrary\Models\LibraryItem;

trait LibraryUser
{
    /**
     * Get the user's personal folder.
     */
    public function personalFolder()
    {
        return $this->belongsTo(LibraryItem::class, 'personal_folder_id');
    }

    /**
     * Get or create the user's personal folder.
     */
    public function getPersonalFolder(): LibraryItem
    {
        return LibraryItem::ensurePersonalFolder($this);
    }

    /**
     * Get the library items favorited by this user.
     */
    public function favoriteLibraryItems()
    {
        return $this->belongsToMany(LibraryItem::class, 'library_item_favorites')
            ->withTimestamps();
    }

    /**
     * Check if the user is a library admin.
     *
     * Library admins can:
     * - View all library items (including root items)
     * - Edit any library item
     * - Delete any library item
     * - Manage permissions on any item
     * - Access all library functionality
     *
     * Override this method to add role-based logic.
     */
    public function isLibraryAdmin(): bool
    {
        // Default implementation - override this method to add role-based logic
        // For example: return $this->hasRole('admin') || $this->hasRole('library-admin');
        return false;
    }
}
