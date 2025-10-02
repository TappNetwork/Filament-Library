<?php

namespace Tapp\FilamentLibrary\Traits;

use Tapp\FilamentLibrary\Models\LibraryItem;

trait LibraryUser
{
    /**
     * Get the user's personal folder.
     *
     * This trait provides library-related functionality for users.
     * It can be extended in the future to include additional
     * library features like favorites, recent items, etc.
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
}
