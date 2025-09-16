<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Library Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the Filament Library plugin.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Maximum Nesting Depth
    |--------------------------------------------------------------------------
    |
    | The maximum number of levels deep that folders can be nested.
    | Set to null for unlimited nesting.
    |
    */
    'max_nesting_depth' => 5,

    /*
    |--------------------------------------------------------------------------
    | Soft Delete Days
    |--------------------------------------------------------------------------
    |
    | Number of days to keep soft-deleted items before permanent deletion.
    | Set to null to disable automatic cleanup.
    |
    */
    'soft_delete_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Table Columns
    |--------------------------------------------------------------------------
    |
    | Configuration for which columns to show in the library table.
    |
    */
    'table_columns' => [
        'name' => true,
        'type' => true,
        'size' => true,
        'created_at' => true,
        'updated_at' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Operations
    |--------------------------------------------------------------------------
    |
    | Configuration for file upload and handling.
    |
    */
    'file_operations' => [
        'allowed_mime_types' => [
            'image/*',
            'application/pdf',
            'text/*',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/zip',
            'application/x-rar-compressed',
        ],
        'max_file_size' => 50 * 1024 * 1024, // 50MB default
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Library Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Spatie Media Library integration.
    | Most settings are handled by the Media Library's own configuration.
    |
    */
    'media_library' => [
        'collection_name' => 'files',
        'conversion_name' => 'thumb',
        'disk' => null, // Use Media Library's default disk
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for permission handling and caching.
    |
    */
    'permissions' => [
        'cache_ttl' => 3600, // 1 hour
        'auto_inherit' => true, // Automatically inherit parent permissions
        'cascade_on_change' => true, // Cascade permission changes to children
    ],

    /*
    |--------------------------------------------------------------------------
    | User Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for user model integration.
    |
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Navigation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Filament navigation integration.
    |
    */
    'navigation' => [
        'group' => 'Library',
        'icon' => 'heroicon-o-folder',
        'sort' => 10,
    ],
];

