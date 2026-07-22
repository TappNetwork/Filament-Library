<?php

namespace Tapp\FilamentLibrary\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tapp\FilamentLibrary\FilamentLibraryPlugin;
use Tapp\FilamentLibrary\Models\LibraryItem;

/**
 * @method BelongsToMany favoriteLibraryItems()
 */
trait LibraryUser
{
    /**
     * Get the user's personal folder.
     */
    public function personalFolder()
    {
        return $this->belongsTo(FilamentLibraryPlugin::libraryItemModelClass(), 'personal_folder_id');
    }

    /**
     * Get or create the user's personal folder.
     */
    public function getPersonalFolder(): LibraryItem
    {
        return FilamentLibraryPlugin::libraryItemModelClass()::ensurePersonalFolder($this);
    }

    /**
     * Get the library items favorited by this user.
     */
    public function favoriteLibraryItems(): BelongsToMany
    {
        return $this->belongsToMany(FilamentLibraryPlugin::libraryItemModelClass(), 'library_item_favorites')
            ->withTimestamps();
    }
}
