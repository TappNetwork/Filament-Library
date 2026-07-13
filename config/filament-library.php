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
    | Sharing behavior
    |--------------------------------------------------------------------------
    |
    | Defaults preserve the classic package UX. Host apps can opt into the
    | newer "shared items live in the main library" workflow.
    |
    */
    'sharing' => [
        /*
        | Show the Shared with Me navigation item and page.
        */
        'shared_with_me' => env('FILAMENT_LIBRARY_SHARED_WITH_ME', true),

        /*
        | When true, items with explicit per-user permissions appear in the main
        | Library view (root-level shared folders/files and nested shared items).
        | When false, those items only appear on the Shared with Me page.
        */
        'show_nested_shared_in_library' => env('FILAMENT_LIBRARY_SHOW_NESTED_SHARED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission assignment UI
    |--------------------------------------------------------------------------
    |
    | Defaults keep the classic single-user permission form and hide the
    | library-table bulk Manage Permissions action.
    |
    */
    'permissions' => [
        /*
        | Show Manage Permissions as a table bulk action.
        */
        'bulk_manage_action' => env('FILAMENT_LIBRARY_BULK_MANAGE_PERMISSIONS', false),

        /*
        | When true, the permissions relation manager uses filter-based
        | multi-user assignment. When false, it uses the classic single-user form.
        */
        'filter_based_assignment' => env('FILAMENT_LIBRARY_FILTER_BASED_ASSIGNMENT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | User assignment filters
    |--------------------------------------------------------------------------
    |
    | Optional filters shown when filter-based assignment (or the bulk manage
    | action) is enabled. Host apps can enable community and role filters by
    | pointing these options at their own models.
    |
    */
    'user_filters' => [
        'community' => [
            'enabled' => false,
            'model' => null,
            'title_attribute' => 'name',
            'user_foreign_key' => 'community_id',
        ],
        'role' => [
            'enabled' => false,
            'model' => null,
            'title_attribute' => 'name',
        ],
        'signup_date' => [
            'enabled' => false,
            'column' => 'created_at',
        ],
    ],

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
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'breadcrumbs_ttl_seconds' => 300,
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
