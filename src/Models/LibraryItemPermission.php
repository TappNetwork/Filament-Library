<?php

namespace Tapp\FilamentLibrary\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Tapp\FilamentLibrary\Models\Traits\BelongsToTenant;

/**
 * @property int $id
 * @property int $library_item_id
 * @property int $user_id
 * @property string $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read LibraryItem $libraryItem
 * @property-read Model $user
 */
class LibraryItemPermission extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'library_item_permissions';

    protected $fillable = [
        'library_item_id',
        'user_id',
        'role',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the library item this permission belongs to.
     */
    public function libraryItem(): BelongsTo
    {
        return $this->belongsTo(config('filament-library.models.LibraryItem', LibraryItem::class));
    }

    /**
     * Get the user this permission belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('filament-library.user_model'));
    }

    /**
     * Get the available role options.
     */
    public static function getRoleOptions(): array
    {
        return [
            'viewer' => 'Viewer',
            'editor' => 'Editor',
            'owner' => 'Owner',
        ];
    }

    /**
     * Check if this permission allows editing.
     */
    public function canEdit(): bool
    {
        return in_array($this->role, ['editor', 'owner']);
    }

    /**
     * Check if this permission allows viewing.
     */
    public function canView(): bool
    {
        return in_array($this->role, ['viewer', 'editor', 'owner']);
    }
}
