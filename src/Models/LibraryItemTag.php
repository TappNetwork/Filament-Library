<?php

namespace Tapp\FilamentLibrary\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tapp\FilamentLibrary\Models\Traits\BelongsToTenant;

class LibraryItemTag extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the color for this tag based on its ID.
     * Uses Filament's standard semantic color names for proper styling.
     */
    public function getColorFromId(): string
    {
        $colors = [
            'primary',
            'success',
            'warning',
            'danger',
            'info',
            'gray',
        ];

        return $colors[$this->id % count($colors)];
    }
}
