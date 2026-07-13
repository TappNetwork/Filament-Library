<?php

namespace Tapp\FilamentLibrary\Support;

use Illuminate\Database\Eloquent\Builder;
use Tapp\FilamentLibrary\Contracts\UserAssignmentFilterProvider;

class NullUserAssignmentFilterProvider implements UserAssignmentFilterProvider
{
    /**
     * @return array<int, never>
     */
    public function schema(): array
    {
        return [];
    }

    /**
     * @return list<never>
     */
    public function filterKeys(): array
    {
        return [];
    }

    public function apply(Builder $query, array $filters): Builder
    {
        return $query;
    }
}
