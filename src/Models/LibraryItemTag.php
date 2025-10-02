<?php

namespace Tapp\FilamentLibrary\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LibraryItemTag extends Model
{
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
     */
    public function getColorFromId(): string
    {
        $colors = [
            'primary', 'secondary', 'success', 'danger', 'warning', 'info',
            'gray', 'slate', 'zinc', 'neutral', 'stone', 'red', 'orange',
            'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan',
            'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose'
        ];

        return $colors[$this->id % count($colors)];
    }
}
