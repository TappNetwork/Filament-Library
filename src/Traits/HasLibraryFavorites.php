<?php

namespace Tapp\FilamentLibrary\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tapp\FilamentLibrary\Models\LibraryItem;

trait HasLibraryFavorites
{
    /**
     * Get the library items favorited by this user.
     */
    public function favoriteLibraryItems(): BelongsToMany
    {
        return $this->belongsToMany(LibraryItem::class, 'library_item_favorites')
            ->withTimestamps();
    }
}
