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

];
