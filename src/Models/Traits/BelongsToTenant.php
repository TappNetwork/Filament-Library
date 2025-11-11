<?php

namespace Tapp\FilamentLibrary\Models\Traits;

trait BelongsToTenant
{
    /**
     * Boot the BelongsToTenant trait.
     */
    public static function bootBelongsToTenant(): void
    {
        // Skip if tenancy is disabled
        if (! config('filament-library.tenancy.enabled')) {
            return;
        }

        // Dynamically define the tenant relationship
        static::resolveRelationUsing(
            static::getTenantRelationshipName(),
            function ($model) {
                return $model->belongsTo(config('filament-library.tenancy.model'), static::getTenantColumnName());
            }
        );

        // Automatically set tenant_id when creating a new model
        static::creating(function ($model) {
            $tenantColumnName = static::getTenantColumnName();

            // Early exit if tenant foreign key is already set (e.g., by Filament's observer)
            if (! empty($model->{$tenantColumnName})) {
                return;
            }

            $tenantRelationshipName = static::getTenantRelationshipName();

            // Try to get tenant from Filament context (Filament's standard method)
            if (class_exists(\Filament\Facades\Filament::class)) {
                $tenant = \Filament\Facades\Filament::getTenant();

                if ($tenant) {
                    // Use Laravel's associate() method on the BelongsTo relationship
                    $model->{$tenantRelationshipName}()->associate($tenant);

                    return;
                }
            }

            // If still not set, try to infer from parent relationships
            // For LibraryItemPermission, get tenant from its LibraryItem
            if (method_exists($model, 'libraryItem') && isset($model->library_item_id)) {
                $parentItemId = $model->library_item_id;
                $parentItemClass = get_class($model->libraryItem()->getRelated());
                $parentItem = $parentItemClass::find($parentItemId);

                if ($parentItem) {
                    $parentTenant = $parentItem->{$tenantRelationshipName};

                    if ($parentTenant) {
                        $model->{$tenantRelationshipName}()->associate($parentTenant);
                    }
                }
            }
        });
    }

    /**
     * Get the tenant relationship.
     */
    public function tenant()
    {
        if (! config('filament-library.tenancy.enabled')) {
            return null;
        }

        $tenantModel = config('filament-library.tenancy.model');

        if (! $tenantModel) {
            return null;
        }

        return $this->belongsTo($tenantModel, static::getTenantColumnName());
    }

    /**
     * Get the name of the tenant relationship.
     */
    public static function getTenantRelationshipName(): string
    {
        if (! config('filament-library.tenancy.enabled')) {
            return 'tenant';
        }

        return config('filament-library.tenancy.relationship_name') ?? 'tenant';
    }

    /**
     * Get the name of the tenant column.
     */
    public static function getTenantColumnName(): string
    {
        if (! config('filament-library.tenancy.enabled')) {
            return 'tenant_id';
        }

        return config('filament-library.tenancy.column') ?? 'team_id';
    }
}

