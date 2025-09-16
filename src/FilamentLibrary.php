<?php

namespace Tapp\FilamentLibrary;

class FilamentLibrary
{
    /**
     * Get the plugin version.
     */
    public static function version(): string
    {
        return '1.0.0';
    }

    /**
     * Get the plugin name.
     */
    public static function name(): string
    {
        return 'Filament Library';
    }

    /**
     * Get the plugin description.
     */
    public static function description(): string
    {
        return 'A Google Drive-like file management system for Filament';
    }
}
