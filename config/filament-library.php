<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Library Admin Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how library admin access is determined. You can either:
    | 1. Use the default role-based system (requires Spatie Permission)
    | 2. Set a custom callback function
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Admin Role
    |--------------------------------------------------------------------------
    |
    | The default role name used to determine library admin access.
    | This is used when no custom callback is set.
    |
    */
    'admin_role' => env('LIBRARY_ADMIN_ROLE', 'Admin'),

    /*
    |--------------------------------------------------------------------------
    | Custom Admin Callback
    |--------------------------------------------------------------------------
    |
    | Set a custom callback to determine library admin access.
    | This overrides the default role-based system.
    |
    | Example:
    | 'admin_callback' => function($user) {
    |     return $user->hasRole('super-admin') || $user->is_superuser;
    | }
    |
    */
    'admin_callback' => null,
];
