<?php

declare(strict_types=1);

namespace Tapp\FilamentLibrary\Enums;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

enum LibrarySort: string
{
    case FoldersThenName = 'folders_then_name';
    case Name = 'name';
    case CreatedAt = 'created_at';
    case UpdatedAt = 'updated_at';

    public function column(): ?string
    {
        return match ($this) {
            self::FoldersThenName => null,
            self::Name => 'name',
            self::CreatedAt => 'created_at',
            self::UpdatedAt => 'updated_at',
        };
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function apply(Builder $query, string $direction): Builder
    {
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        if ($this === self::FoldersThenName) {
            return $query
                ->orderByRaw('case when type = ? then 0 else 1 end', ['folder'])
                ->orderBy('name', $direction);
        }

        return $query->orderBy((string) $this->column(), $direction);
    }
}
