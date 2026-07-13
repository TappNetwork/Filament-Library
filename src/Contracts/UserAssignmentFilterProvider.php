<?php

namespace Tapp\FilamentLibrary\Contracts;

use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Builder;

interface UserAssignmentFilterProvider
{
    /**
     * Filter form fields shown before the users multi-select.
     *
     * @return array<int, Field>
     */
    public function schema(): array;

    /**
     * Form field names whose values are passed to {@see apply()}.
     *
     * @return list<string>
     */
    public function filterKeys(): array;

    /**
     * Scope the user query using filter form state.
     *
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters): Builder;
}
