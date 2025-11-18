<?php

namespace Tapp\FilamentLibrary\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryItemRolePermission extends Model
{
    use HasFactory;

    protected $table = 'library_item_role_permissions';

    protected $fillable = [
        'library_item_id',
        'role_name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the library item this role permission belongs to.
     */
    public function libraryItem(): BelongsTo
    {
        return $this->belongsTo(LibraryItem::class);
    }
}
