<?php

namespace Tapp\FilamentLibrary\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class LibraryItem extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'parent_id',
        'created_by',
        'updated_by',
        'external_url',
        'link_icon',
        'link_description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $item) {
            if (empty($item->slug)) {
                $item->slug = static::generateUniqueSlug($item->name, $item->parent_id);
            }

            // Set created_by and updated_by on creation (like Laravel does with timestamps)
            if (auth()->check()) {
                $item->created_by = auth()->id();
                $item->updated_by = auth()->id(); // Set both on creation
            }
        });

        static::updating(function (self $item) {
            if ($item->isDirty('name') && ! $item->isDirty('slug')) {
                $item->slug = static::generateUniqueSlug($item->name, $item->parent_id, $item->id);
            }

            // Set updated_by on updates
            if (auth()->check()) {
                $item->updated_by = auth()->id();
            }
        });
    }

    /**
     * Get the parent folder.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(LibraryItem::class, 'parent_id');
    }

    /**
     * Get the child items.
     */
    public function children(): HasMany
    {
        return $this->hasMany(LibraryItem::class, 'parent_id');
    }

    /**
     * Get the user who created this item.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who last updated this item.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Get the permissions for this item.
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(LibraryItemPermission::class);
    }

    /**
     * Scope to get only folders.
     */
    public function scopeFolders($query)
    {
        return $query->where('type', 'folder');
    }

    /**
     * Scope to get only files.
     */
    public function scopeFiles($query)
    {
        return $query->where('type', 'file');
    }

    /**
     * Scope to get only links.
     */
    public function scopeLinks($query)
    {
        return $query->where('type', 'link');
    }

    /**
     * Scope to get items accessible by a user.
     */
    public function scopeForUser($query, $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('created_by', $user->id)
                ->orWhereHas('permissions', function ($permissionQuery) use ($user) {
                    $permissionQuery->where('user_id', $user->id);
                });
        });
    }

    /**
     * Check if a user has a specific permission on this item.
     */
    public function hasPermission($user, string $permission): bool
    {
        // Check direct permissions
        $directPermission = $this->permissions()
            ->where('user_id', $user->id)
            ->where('permission', $permission)
            ->exists();

        if ($directPermission) {
            return true;
        }

        // Check inherited permissions from parent folders
        if ($this->parent_id) {
            return $this->parent->hasPermission($user, $permission);
        }

        return false;
    }

    /**
     * Get the full path of this item.
     */
    public function getPath(): string
    {
        $path = collect([$this->name]);
        $parent = $this->parent;

        while ($parent) {
            $path->prepend($parent->name);
            $parent = $parent->parent;
        }

        return $path->implode('/');
    }

    /**
     * Get the file size for files.
     */
    public function getSizeAttribute(): ?int
    {
        if ($this->type !== 'file') {
            return null;
        }

        $media = $this->getFirstMedia('files');

        return $media ? $media->size : null;
    }

    /**
     * Check if the external URL is a video URL.
     */
    public function isVideoUrl(): bool
    {
        if (! $this->external_url || $this->type !== 'link') {
            return false;
        }

        $videoDomains = [
            'youtube.com',
            'youtu.be',
            'vimeo.com',
            'wistia.com',
        ];

        foreach ($videoDomains as $domain) {
            if (str_contains($this->external_url, $domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the appropriate display icon for this item.
     */
    public function getDisplayIcon(): string
    {
        return match ($this->type) {
            'folder' => 'heroicon-s-folder',
            'file' => 'heroicon-o-document',
            'link' => $this->link_icon ?? 'heroicon-o-link',
            default => 'heroicon-o-document',
        };
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('files')
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'application/pdf',
                'text/plain',
                'text/csv',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ])
            ->singleFile();
    }

    /**
     * Register media conversions.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300);
    }

    /**
     * Generate a unique slug for the given name and parent.
     */
    protected static function generateUniqueSlug(string $name, ?int $parentId = null, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        // Check for existing slugs (including soft-deleted ones)
        while (static::withTrashed()
            ->where('slug', $slug)
            ->where('parent_id', $parentId)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
