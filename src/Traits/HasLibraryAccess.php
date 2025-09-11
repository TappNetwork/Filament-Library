<?php

namespace Tapp\FilamentLibrary\Traits;

use Tapp\FilamentLibrary\Models\LibraryItem;

trait HasLibraryAccess
{
    /**
     * Check if the user can view a specific library item.
     */
    public function canViewLibraryItem(LibraryItem $item): bool
    {
        // Default implementation: creator can see their items, admin can see all
        if ($this->isLibraryAdmin()) {
            return true;
        }
        
        return $item->created_by === $this->id || $item->hasPermission($this, 'view');
    }

    /**
     * Check if the user can edit a specific library item.
     */
    public function canEditLibraryItem(LibraryItem $item): bool
    {
        // Default implementation: creator can edit their items, admin can edit all
        if ($this->isLibraryAdmin()) {
            return true;
        }
        
        return $item->created_by === $this->id || $item->hasPermission($this, 'edit');
    }

    /**
     * Check if the user can view root library items.
     */
    public function canViewRootLibraryItems(): bool
    {
        // Default: only admin can see root items, can be overridden
        return $this->isLibraryAdmin();
    }

    /**
     * Check if the user is a library admin.
     * 
     * Override this method to add role-based logic.
     */
    public function isLibraryAdmin(): bool
    {
        // Default implementation - override this method to add role-based logic
        // For example: return $this->hasRole('admin') || $this->hasRole('library-admin');
        return false;
    }

    /**
     * Get all library items this user can view.
     */
    public function getAccessibleLibraryItems()
    {
        return LibraryItem::forUser($this)->get();
    }

    /**
     * Get root library items this user can view.
     */
    public function getAccessibleRootLibraryItems()
    {
        if (!$this->canViewRootLibraryItems()) {
            return collect();
        }

        return LibraryItem::whereNull('parent_id')->forUser($this)->get();
    }
}
