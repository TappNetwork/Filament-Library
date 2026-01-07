# Multi-Tenancy Support in Filament Library

This guide provides detailed instructions for setting up multi-tenancy in Filament Library.

## ⚠️ CRITICAL: Enable Tenancy Before Migrations

**You MUST configure and enable tenancy in the config file BEFORE running the plugin's migrations.**

The migrations check the `filament-library.tenancy.enabled` config value to determine whether to add tenant columns (e.g.: `team_id` or your custom column) to the database tables. If you enable tenancy after running migrations, you'll need to manually add the tenant columns to your database.

## Setup Order

1. **First**: Configure tenancy in your application's Filament panel
2. **Second**: Publish and configure the Filament Library config file
3. **Third**: Enable tenancy in the Library config
4. **Fourth**: Run the migrations

## Step-by-Step Setup

### 1. Configure Tenancy in Your Filament Panel

First, set up multi-tenancy in your Filament admin panel (in the examples below we are using `Team` as tenant):

```php
// app/Providers/Filament/AdminPanelProvider.php

use App\Models\Team;

public function panel(Panel $panel): Panel
{
    return $panel
        ->tenant(Team::class)
        // ... other configuration
}
```

Make sure your `User` model implements the required contracts:

```php
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class);
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->teams;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->teams()->whereKey($tenant)->exists();
    }
}
```

Your `Team` model should implement `HasName`:

```php
use Filament\Models\Contracts\HasName;

class Team extends Model implements HasName
{
    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
```

### 2. Publish the Library Config File

Publish the Filament Library config file:

```bash
php artisan vendor:publish --tag="filament-library-config"
```

### 3. Enable Tenancy in the Library Config

Edit `config/filament-library.php` and configure the tenancy settings:

```php
'tenancy' => [
    /*
    | Enable or disable tenancy support
    */
    'enabled' => true, // ⚠️ Set this BEFORE running migrations!

    /*
    | The tenant model class (e.g., App\Models\Team::class)
    */
    'model' => \App\Models\Team::class,

    /*
    | The name of the relationship to the tenant (optional, defaults to 'tenant')
    */
    'relationship_name' => 'team', // Optional: customize relationship name

    /*
    | The name of the tenant foreign key column (optional, defaults to 'team_id')
    */
    'column' => 'team_id', // Optional: customize column name
],
```

### 4. Run the Migrations

Now run the migrations. The tenant columns will be added automatically:

```bash
php artisan migrate
```

## What Gets Added to the Database

When tenancy is enabled, the following tables get a tenant foreign key column:

- `library_items` - gets `team_id` (or your custom column name)
- `library_item_permissions` - gets `team_id`
- `library_item_tags` - gets `team_id`

The `library_item_favorites` pivot table does NOT get a tenant column as it's a simple many-to-many pivot between users and library items.

## How It Works

### Automatic Tenant Assignment

When you create library items, permissions, or tags through Filament, the tenant ID is automatically assigned:

1. **For resources with dedicated Filament Resources** (like `LibraryItem`):
   - Filament's built-in observer automatically sets the tenant ID
   - This happens seamlessly when you create items through the admin panel

2. **For child models** (like `LibraryItemPermission`):
   - If the tenant ID is already set, nothing happens (Filament's observer took care of it)
   - If not set, the trait automatically inherits the tenant from the parent `LibraryItem`

3. **For models without dedicated resources** (created via relation managers or custom code):
   - The `BelongsToTenant` trait provides a fallback
   - It first checks if Filament has already set the tenant
   - If not, it tries to inherit from parent relationships
   - This ensures tenant ID is always set correctly

### Parent-Child Relationships

The tenant is automatically inherited through these relationships:

- `LibraryItemPermission` → inherits from `LibraryItem`
- Child `LibraryItem` (when `parent_id` is set) → inherits from parent `LibraryItem`
- `LibraryItemTag` → gets tenant from Filament context

### URL Structure

With tenancy enabled, your Library URLs will include the tenant:

```
/admin/{tenant}/library
/admin/{tenant}/library/my-documents
/admin/{tenant}/library/shared-with-me
```

For example:
```
/admin/acme-corp/library
/admin/acme-corp/library/1/edit-file
```

### Scoping

All Library resources are automatically scoped to the current tenant:

- Users can only see library items belonging to their current tenant
- Permissions and tags are also scoped to the tenant
- Personal folders are created per tenant (a user has a separate personal folder for each team)

## Disabling Tenancy

If you need to disable tenancy after enabling it:

1. Set `'enabled' => false` in `config/filament-library.php`
2. Note: This will NOT remove the tenant columns from your database
3. The columns will simply be ignored by the application

## Important Notes

- **Migration Order**: You MUST enable tenancy in the config BEFORE running migrations
- **Existing Data**: If you have existing library data and enable tenancy later, you'll need to manually add tenant columns and populate them
- **Personal Folders**: Each user gets a separate personal folder per tenant
- **URL Changes**: Enabling tenancy changes your URL structure to include the tenant slug
- **Access Control**: Make sure users are assigned to teams to access the Library

## Troubleshooting

### "Field 'team_id' doesn't have a default value" Error

This means:
1. Tenancy is enabled in the config
2. The migration added the `team_id` column
3. But the model can't determine the tenant automatically

**Solution**: Make sure you're creating library items through Filament's admin panel with a tenant context, or manually set the tenant when creating items programmatically.

### Existing Data Migration

If you need to add tenancy to an existing installation:

1. Backup your database
2. Manually add the tenant columns:
   ```sql
   ALTER TABLE library_items ADD COLUMN team_id BIGINT UNSIGNED NOT NULL;
   ALTER TABLE library_item_permissions ADD COLUMN team_id BIGINT UNSIGNED NOT NULL;
   ALTER TABLE library_item_tags ADD COLUMN team_id BIGINT UNSIGNED NOT NULL;
   
   -- Add foreign keys
   ALTER TABLE library_items ADD FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE;
   ALTER TABLE library_item_permissions ADD FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE;
   ALTER TABLE library_item_tags ADD FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE;
   ```
3. Update existing records with appropriate tenant IDs
4. Enable tenancy in the config

## Support

For issues or questions about multi-tenancy in Filament Library, please open an issue on the GitHub repository.
