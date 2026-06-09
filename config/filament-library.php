<?php

use Tapp\FilamentLibrary\Models\LibraryItem;
use Tapp\FilamentLibrary\Models\LibraryItemPermission;
use Tapp\FilamentLibrary\Models\LibraryItemTag;
use Tapp\FilamentLibrary\Resources\LibraryItemResource;

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
    | Model class overrides
    |--------------------------------------------------------------------------
    |
    | Extend package models in your application and register the classes here
    | when you need custom behavior. Each class must extend the corresponding
    | Tapp\FilamentLibrary\Models\* model.
    |
    | Example (in your app's config/filament-library.php):
    |
    | 'models' => [
    |     'LibraryItem' => \App\Models\LibraryItem::class,
    |     'LibraryItemPermission' => \App\Models\LibraryItemPermission::class,
    |     'LibraryItemTag' => \App\Models\LibraryItemTag::class,
    | ],
    |
    */
    'models' => [
        'LibraryItem' => LibraryItem::class,
        'LibraryItemPermission' => LibraryItemPermission::class,
        'LibraryItemTag' => LibraryItemTag::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament resource class overrides
    |--------------------------------------------------------------------------
    |
    | Use this option to custom Filament Resource classes
    |
    | Example (in your app's config/filament-library.php):
    |
    | 'resources' => [
    |     'LibraryItemResource' => \App\Filament\Resources\Library\LibraryItemResource::class,
    | ],
    |
    */
    'resources' => [
        'LibraryItemResource' => LibraryItemResource::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Personal folder provisioning
    |--------------------------------------------------------------------------
    |
    | When enabled, the plugin registers a listener that creates a personal
    | library folder when a user is created. Disable this when your app
    | provisions personal folders itself (for example with tenant checks).
    |
    */
    'personal_folder' => [
        'auto_create_on_user_created' => env('FILAMENT_LIBRARY_AUTO_CREATE_PERSONAL_FOLDER', true),
    ],

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

    /*
    |--------------------------------------------------------------------------
    | File Preview Configuration
    |--------------------------------------------------------------------------
    |
    | Configure text-based file previews for markdown and JSON exports.
    |
    */
    'preview' => [
        'text_max_bytes' => 2 * 1024 * 1024,
        'markdown_extensions' => ['md', 'markdown', 'mdown'],
        'json_filename_patterns' => [
            'quiz' => ['quiz'],
            'flashcards' => ['flashcard', 'flashcards'],
            'mindmap' => ['mindmap', 'mind-map', 'mind_map'],
        ],
    ],

];
