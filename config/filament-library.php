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
    | Video Link Support
    |--------------------------------------------------------------------------
    |
    | Configure which video platforms are supported for link embeds.
    | When a library item is a link to one of these domains, it will be
    | treated as a video link and displayed accordingly.
    |
    */
    'video' => [
        'supported_domains' => [
            'youtube.com',
            'youtu.be',
            'vimeo.com',
            'wistia.com',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | URL Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how library item URLs are generated and secured.
    |
    */
    'url' => [
        /*
        | Number of minutes that temporary URLs remain valid.
        | Used when generating secure download links for files.
        */
        'temporary_expiration_minutes' => 60,
    ],

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
