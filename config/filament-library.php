<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | This is the user model that will be used for relationships in the
    | filament-library package. You can override this to use your own
    | user model.
    |
    */
    'user_model' => env('FILAMENT_LIBRARY_USER_MODEL', 'App\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy Configuration
    |--------------------------------------------------------------------------
    |
    | Enable multi-tenancy support for the Library plugin. When enabled,
    | library items, permissions, and tags will be scoped to tenants.
    |
    | IMPORTANT: You must configure and enable tenancy BEFORE running
    | the migrations. The migrations check this config to determine
    | whether to add tenant columns to the database tables.
    |
    */
    'tenancy' => [
        /*
        | Enable or disable tenancy support
        */
        'enabled' => false,

        /*
        | The tenant model class (e.g., App\Models\Team::class)
        */
        'model' => null,

        /*
        | The name of the relationship to the tenant (optional, defaults to 'tenant')
        */
        'relationship_name' => null,

        /*
        | The name of the tenant foreign key column (optional, defaults to 'team_id')
        */
        'column' => null,
    ],

];
