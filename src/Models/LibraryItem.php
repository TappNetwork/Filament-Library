<?php

namespace Tapp\FilamentLibrary\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tapp\FilamentLibrary\Enums\LibraryPublicationStatus;
use Tapp\FilamentLibrary\Events\LibraryItemPublished;
use Tapp\FilamentLibrary\Events\LibraryItemRejected;
use Tapp\FilamentLibrary\FilamentLibraryPlugin;
use Tapp\FilamentLibrary\Models\Traits\BelongsToTenant;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $type
 * @property int|null $parent_id
 * @property int $created_by
 * @property int|null $updated_by
 * @property string|null $external_url
 * @property string|null $link_description
 * @property string|null $general_access
 * @property string|null $firm_path
 * @property string|null $library_area
 * @property string|null $committee
 * @property array<int, string>|null $project_tags
 * @property LibraryPublicationStatus|null $publication_status
 * @property Carbon|null $published_at
 * @property int|null $published_by
 * @property Carbon|null $rejected_at
 * @property int|null $rejected_by
 * @property string|null $rejection_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read LibraryItem|null $parent
 * @property-read Collection<int, LibraryItem> $children
 * @property-read Model $creator
 * @property-read Model|null $updater
 * @property-read Collection<int, LibraryItemPermission> $permissions
 * @property-read Collection<int, LibraryItemTag> $tags
 */
class LibraryItem extends Model implements HasMedia
{
    use BelongsToTenant;
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    protected static bool $syncingProjectTags = false;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'parent_id',
        'created_by',
        'updated_by',
        'external_url',
        'link_description',
        'general_access',
        'firm_path',
        'library_area',
        'committee',
        'project_tags',
        'publication_status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'project_tags' => 'array',
        'publication_status' => LibraryPublicationStatus::class,
        'published_at' => 'datetime',
        'rejected_at' => 'datetime',
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

            if ($item->publication_status === null) {
                $item->publication_status = config('filament-library.publication.new_items_require_approval', false)
                    ? LibraryPublicationStatus::Pending
                    : LibraryPublicationStatus::Published;
            }

            // Set created_by and updated_by on creation (like Laravel does with timestamps)
            if (auth()->check()) {
                $item->created_by = auth()->id();
                $item->updated_by = auth()->id(); // Set both on creation
            }
        });

        static::created(function (self $item) {
            // Copy parent folder permissions to the new item
            if ($item->parent_id && $item->parent) {
                $parentPermissions = $item->parent->permissions()->get();

                foreach ($parentPermissions as $permission) {
                    if (isset($permission->user_id) && isset($permission->role)) {
                        $item->permissions()->create([
                            'user_id' => $permission->user_id,
                            'role' => $permission->role,
                        ]);
                    }
                }
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

        static::saved(function (self $item): void {
            $shouldSync = is_array($item->project_tags)
                && ($item->wasRecentlyCreated || $item->wasChanged('project_tags'));

            if (static::$syncingProjectTags || ! $shouldSync) {
                return;
            }

            static::$syncingProjectTags = true;

            try {
                $item->syncProjectTagRecords();
            } finally {
                static::$syncingProjectTags = false;
            }
        });
    }

    /**
     * Get the parent folder.
     */
    public function parent(): BelongsTo
    {
        $libraryItemModel = config('filament-library.models.LibraryItem', static::class);

        return $this->belongsTo($libraryItemModel, 'parent_id');
    }

    /**
     * Get the child items.
     */
    public function children(): HasMany
    {
        $libraryItemModel = config('filament-library.models.LibraryItem', static::class);

        return $this->hasMany($libraryItemModel, 'parent_id');
    }

    /**
     * Get the user who created this item.
     */
    public function creator(): BelongsTo
    {
        $userModel = config('filament-library.user_model', config('auth.providers.users.model', 'App\\Models\\User'));

        return $this->belongsTo($userModel, 'created_by')->withDefault(function ($instance) {
            // Check if 'name' field exists
            if (Schema::hasColumn('users', 'name')) {
                $instance->name = 'Unknown User';
                $instance->email = 'deleted@example.com';
            } else {
                // Fall back to first_name/last_name
                $instance->first_name = 'Unknown';
                $instance->last_name = 'User';
                $instance->email = 'deleted@example.com';
            }
        });
    }

    /**
     * Get the user who last updated this item.
     */
    public function updater(): BelongsTo
    {
        $userModel = config('filament-library.user_model', config('auth.providers.users.model', 'App\\Models\\User'));

        return $this->belongsTo($userModel, 'updated_by');
    }

    /**
     * Get the permissions for this item.
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(config('filament-library.models.LibraryItemPermission', LibraryItemPermission::class));
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
     * Get the effective role for a user on this item.
     */
    public function getEffectiveRole($user): ?string
    {
        if (! $user) {
            return null;
        }

        // Check if user is the creator (always has access)
        if ($this->created_by === $user->id) {
            // Creator always has access, but check if they're also the owner
            $currentOwner = $this->getCurrentOwner();
            if ($currentOwner && $currentOwner->id === $user->id) {
                return 'owner'; // Creator is also owner
            }

            return 'creator'; // Creator but not owner
        }

        // Check direct resource permissions
        $directPermission = $this->permissions()
            ->where('user_id', $user->id)
            ->first();

        if ($directPermission && isset($directPermission->role)) {
            return $directPermission->role;
        }

        // Check inherited permissions from parent folders
        if ($this->parent_id) {
            return $this->parent->getEffectiveRole($user);
        }

        // Check general access
        $effectiveGeneralAccess = $this->getEffectiveGeneralAccess();

        if ($effectiveGeneralAccess === 'anyone_can_view') {
            return 'viewer';
        }

        return null; // No access
    }

    /**
     * Get the current owner of this item.
     */
    public function getCurrentOwner(): ?Model
    {
        $ownerPermission = $this->permissions()
            ->where('role', 'owner')
            ->first();

        if ($ownerPermission && $ownerPermission->user) {
            return $ownerPermission->user;
        }

        // If no owner in permissions table, creator is the owner
        return $this->creator;
    }

    /**
     * Check if the creator is the current owner.
     */
    public function isCreatorOwner(): bool
    {
        $currentOwner = $this->getCurrentOwner();

        return $currentOwner && $currentOwner->id === $this->created_by;
    }

    /**
     * Transfer ownership to another user.
     */
    public function transferOwnership(Model $newOwner): void
    {
        // Remove existing owner permissions
        $this->permissions()->where('role', 'owner')->delete();

        // Check if the new owner already has a permission for this item
        $existingPermission = $this->permissions()->where('user_id', $newOwner->id)->first();

        if ($existingPermission) {
            // Update existing permission to owner
            $existingPermission->update(['role' => 'owner']);
        } else {
            // Create new owner permission
            $this->permissions()->create([
                'user_id' => $newOwner->id,
                'role' => 'owner',
            ]);
        }
    }

    /**
     * Ensure a user has a personal folder (like Google Drive's "My Drive").
     */
    public static function ensurePersonalFolder(Model $user): self
    {
        // Check if user already has a personal folder via the relationship
        if ($user->personal_folder_id) {
            $personalFolder = static::find($user->personal_folder_id);
            if ($personalFolder) {
                return $personalFolder;
            }
        }

        // Create personal folder
        $personalFolder = static::create([
            'name' => static::getPersonalFolderName($user),
            'type' => 'folder',
            'parent_id' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'general_access' => 'private',
        ]);

        // Set the user as owner of their personal folder
        $personalFolder->permissions()->create([
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        // Update the user's personal_folder_id
        $user->update(['personal_folder_id' => $personalFolder->id]);

        return $personalFolder;
    }

    /**
     * Get a user's personal folder.
     */
    public static function getPersonalFolder(Model $user): ?self
    {
        if (! $user->personal_folder_id) {
            return null;
        }

        return static::find($user->personal_folder_id);
    }

    /**
     * Generate the personal folder name for a user.
     */
    public static function getPersonalFolderName(Model $user): string
    {
        // Try to get a display name from various user fields
        $name = $user->first_name ?? $user->name ?? $user->email ?? 'User';

        // Clean the name (remove special characters that might cause issues)
        $name = preg_replace('/[^\w\s-]/', '', $name);
        $name = trim($name);

        // Fallback if name is empty
        if (empty($name)) {
            $name = 'User';
        }

        return $name . "'s Personal Folder";
    }

    /**
     * Get the effective general access setting (resolves inheritance).
     */
    public function getEffectiveGeneralAccess(): string
    {
        // Handle null values (existing records before migration)
        if ($this->general_access === null || $this->general_access === 'inherit') {
            // Inherit from parent
            if ($this->parent_id) {
                return $this->parent->getEffectiveGeneralAccess();
            }

            // Root level defaults to private
            return 'private';
        }

        return $this->general_access;
    }

    /**
     * Get the inherited general access value from parent (for display purposes).
     */
    public function getInheritedGeneralAccess(): ?string
    {
        if (! $this->parent_id) {
            return null;
        }

        return $this->parent->getEffectiveGeneralAccess();
    }

    /**
     * Get the display text for inherited general access.
     */
    public function getInheritedGeneralAccessDisplay(): ?string
    {
        $inherited = $this->getInheritedGeneralAccess();

        if (! $inherited) {
            return null;
        }

        $options = self::getGeneralAccessOptions();

        return $options[$inherited] ?? $inherited;
    }

    /**
     * Check if a user has a specific permission on this item.
     */
    public function hasPermission($user, string $permission): bool
    {
        // Admin users always have all permissions
        if ($user && FilamentLibraryPlugin::isLibraryAdmin($user)) {
            return true;
        }

        $effectiveRole = $this->getEffectiveRole($user);

        if (! $effectiveRole) {
            return false;
        }

        return match ($permission) {
            'view' => in_array($effectiveRole, ['viewer', 'editor', 'owner', 'creator']),
            'edit' => in_array($effectiveRole, ['editor', 'owner', 'creator']),
            'share' => in_array($effectiveRole, ['owner', 'creator']),
            'delete' => in_array($effectiveRole, ['owner', 'creator']),
            'upload' => in_array($effectiveRole, ['editor', 'owner', 'creator']),
            'manage_permissions' => in_array($effectiveRole, ['owner', 'creator']),
            default => false,
        };
    }

    /**
     * Check if a user can view this item (including anonymous access).
     */
    public function canBeViewedBy($user = null): bool
    {
        // If user is logged in, check their effective role
        if ($user) {
            return $this->hasPermission($user, 'view');
        }

        // For anonymous users, check if general access allows viewing
        $effectiveGeneralAccess = $this->getEffectiveGeneralAccess();

        return $effectiveGeneralAccess === 'anyone_can_view';
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

        $videoDomains = config('filament-library.video.supported_domains', [
            'youtube.com',
            'youtu.be',
            'vimeo.com',
            'wistia.com',
        ]);

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
            'link' => 'heroicon-o-link',
            default => 'heroicon-o-document',
        };
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        // Using default collection - no need to register custom collection
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
     * Get the media item used for file previews and downloads.
     */
    public function getPreviewMedia(): ?Media
    {
        return $this->getFirstMedia('files') ?? $this->getFirstMedia();
    }

    /**
     * Get a secure URL for the file with temporary URL fallback.
     */
    public function getSecureUrl(?int $expirationMinutes = null): string
    {
        $media = $this->getPreviewMedia();

        if (! $media) {
            return '';
        }

        $expirationMinutes = $expirationMinutes ?? config('filament-library.url.temporary_expiration_minutes', 60);

        try {
            return $media->getTemporaryUrl(now()->addMinutes($expirationMinutes));
        } catch (\Exception $e) {
            $url = $media->getUrl();

            // Ensure HTTPS for security
            if (str_starts_with($url, 'http://')) {
                $url = str_replace('http://', 'https://', $url);
            }

            return $url;
        }
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

    /**
     * Get the access control options for the general access select.
     */
    public static function getAccessControlOptions(): array
    {
        return [
            'inherit' => 'Inherit from parent',
            'private' => 'Private (owner only)',
            'anyone_can_view' => 'Anyone can view',
        ];
    }

    /**
     * Get the effective access control setting.
     */
    public function getEffectiveAccessControl(): string
    {
        // If not inheriting, use the item's own setting
        if ($this->general_access && $this->general_access !== 'inherit') {
            return $this->general_access;
        }

        // If inheriting from parent, check parent's access control
        if ($this->parent_id) {
            return $this->parent->getEffectiveAccessControl();
        }

        // Root level items default to private
        return 'private';
    }

    /**
     * Get the general access options.
     */
    public static function getGeneralAccessOptions(): array
    {
        return [
            'inherit' => 'Inherit from parent',
            'private' => 'Private (owner only)',
            'anyone_can_view' => 'Anyone can view',
        ];
    }

    /**
     * Get the tags for this library item.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(config('filament-library.models.LibraryItemTag', LibraryItemTag::class), 'library_item_tag_pivot')
            ->withTimestamps();
    }

    /**
     * Get the users who favorited this library item.
     * Note: This uses a generic user model - projects should extend this
     * or override this relationship in their own LibraryItem model.
     */
    public function favoritedBy(): BelongsToMany
    {
        // Use a configurable user model or fallback to a generic approach
        $userModel = config('filament-library.user_model', 'App\\Models\\User');

        return $this->belongsToMany($userModel, 'library_item_favorites')
            ->withTimestamps();
    }

    /**
     * Toggle favorite status for the current user.
     */
    public function toggleFavorite(): void
    {
        if (auth()->check()) {
            $user = auth()->user();
            // Assuming the user model uses LibraryUser trait
            // @phpstan-ignore-next-line
            if ($user->favoriteLibraryItems()->where('library_item_id', $this->id)->exists()) {
                // @phpstan-ignore-next-line
                $user->favoriteLibraryItems()->detach($this->id);
            } else {
                // @phpstan-ignore-next-line
                $user->favoriteLibraryItems()->attach($this->id);
            }
        }
    }

    /**
     * Check if this item is favorited by the current user.
     */
    public function isFavorite(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $user = auth()->user();

        // Assuming the user model uses LibraryUser trait
        // @phpstan-ignore-next-line
        return $user->favoriteLibraryItems()->where('library_item_id', $this->id)->exists();
    }

    /**
     * Get the is_favorite attribute.
     */
    public function getIsFavoriteAttribute(): bool
    {
        return $this->isFavorite();
    }

    /**
     * Split a manifest tag list into unique names.
     *
     * @return list<string>
     */
    public static function normalizeProjectTags(array | string | null $tags): array
    {
        if (is_string($tags)) {
            $tags = preg_split('/[|,;]/', $tags) ?: [];
        }

        if ($tags === null) {
            return [];
        }

        $normalized = [];

        foreach ($tags as $tag) {
            $tag = trim((string) $tag);

            if ($tag !== '') {
                $normalized[] = $tag;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Mirror project_tags onto library item tag records.
     */
    public function syncProjectTagRecords(): void
    {
        $names = self::normalizeProjectTags($this->project_tags);
        $tagModel = FilamentLibraryPlugin::libraryItemTagModelClass();
        $ids = [];

        foreach ($names as $name) {
            $ids[] = $this->findOrCreateProjectTag($tagModel, $name)->getKey();
        }

        $this->tags()->sync($ids);
    }

    public function isSearchable(): bool
    {
        return $this->publication_status === LibraryPublicationStatus::Published;
    }

    /**
     * Published items only. Draft, pending, and rejected items stay out of search.
     */
    public function scopeSearchable(Builder $query): Builder
    {
        return static::limitToPublished($query);
    }

    /**
     * Items still waiting on a gatekeeper, including rejections.
     */
    public function scopeUnpublished(Builder $query): Builder
    {
        return static::limitToUnpublished($query);
    }

    public static function limitToPublished(Builder $query): Builder
    {
        return $query->where(
            $query->getModel()->qualifyColumn('publication_status'),
            LibraryPublicationStatus::Published->value,
        );
    }

    public static function limitToUnpublished(Builder $query): Builder
    {
        return $query->where(
            $query->getModel()->qualifyColumn('publication_status'),
            '!=',
            LibraryPublicationStatus::Published->value,
        );
    }

    public function scopeInLibraryArea(Builder $query, string $libraryArea): Builder
    {
        return $query->where($query->getModel()->qualifyColumn('library_area'), $libraryArea);
    }

    public function scopeForCommittee(Builder $query, string $committee): Builder
    {
        return $query->where($query->getModel()->qualifyColumn('committee'), $committee);
    }

    public function scopeWithProjectTag(Builder $query, string $tag): Builder
    {
        return $query->whereJsonContains(
            $query->getModel()->qualifyColumn('project_tags'),
            $tag,
        );
    }

    public function publish(Model | int | null $gatekeeper = null): void
    {
        if ($this->publication_status === LibraryPublicationStatus::Published) {
            return;
        }

        $this->forceFill([
            'publication_status' => LibraryPublicationStatus::Published,
            'published_at' => now(),
            'published_by' => $this->gatekeeperId($gatekeeper),
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
        ])->save();

        event(new LibraryItemPublished($this));
    }

    public function reject(Model | int | null $gatekeeper = null, ?string $reason = null): void
    {
        $reason = $reason !== null ? trim($reason) : null;
        $reason = $reason === '' ? null : $reason;

        $this->forceFill([
            'publication_status' => LibraryPublicationStatus::Rejected,
            'rejected_at' => now(),
            'rejected_by' => $this->gatekeeperId($gatekeeper),
            'rejection_reason' => $reason,
            'published_at' => null,
            'published_by' => null,
        ])->save();

        event(new LibraryItemRejected($this, $reason));
    }

    public function returnToDraft(Model | int | null $gatekeeper = null): void
    {
        $this->forceFill([
            'publication_status' => LibraryPublicationStatus::Draft,
            'published_at' => null,
            'published_by' => null,
            'updated_by' => $this->gatekeeperId($gatekeeper) ?? $this->updated_by,
        ])->save();
    }

    protected function gatekeeperId(Model | int | null $gatekeeper): ?int
    {
        if ($gatekeeper instanceof Model) {
            return $gatekeeper->getKey() !== null ? (int) $gatekeeper->getKey() : null;
        }

        if (is_int($gatekeeper)) {
            return $gatekeeper;
        }

        $authId = auth()->id();

        return $authId !== null ? (int) $authId : null;
    }

    /**
     * @param  class-string<LibraryItemTag>  $tagModel
     */
    protected function findOrCreateProjectTag(string $tagModel, string $name): LibraryItemTag
    {
        $existing = $tagModel::query()->where('name', $name)->first();

        if ($existing instanceof LibraryItemTag) {
            return $existing;
        }

        $baseSlug = Str::slug($name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'tag';
        $slug = $baseSlug;
        $counter = 1;

        while ($tagModel::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        /** @var LibraryItemTag $tag */
        $tag = $tagModel::query()->create([
            'name' => $name,
            'slug' => $slug,
        ]);

        return $tag;
    }
}
