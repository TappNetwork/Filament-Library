<?php

namespace Tapp\FilamentLibrary\Support;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Tapp\FilamentLibrary\Contracts\UserAssignmentFilterProvider;
use Tapp\FilamentLibrary\Forms\Components\UserSearchSelect;

class UserAssignmentFilters
{
    /**
     * @return array<int, Field>
     */
    public static function schema(string $usersField = 'user_ids'): array
    {
        return [
            ...static::filterFields($usersField),
            static::selectAllMatchingToggle($usersField),
            static::usersSelect($usersField),
        ];
    }

    /**
     * @return array<int, Field>
     */
    public static function filterFields(string $usersField = 'user_ids'): array
    {
        $provider = static::provider();

        if (! $provider instanceof UserAssignmentFilterProvider) {
            return [];
        }

        return collect($provider->schema())
            ->map(function (Field $field) use ($usersField): Field {
                return $field
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) use ($usersField): void {
                        static::refreshSelectedUsersIfSelectingAll($get, $set, $usersField);
                    });
            })
            ->all();
    }

    public static function selectAllMatchingToggle(string $usersField = 'user_ids'): Toggle
    {
        return Toggle::make('select_all_matching')
            ->label('Select all matching users')
            ->helperText('Selects every user matching the filters above, not just the first 50 search results. With no filters, this selects all users.')
            ->live()
            ->afterStateUpdated(function (Get $get, Set $set, mixed $state) use ($usersField): void {
                if ($state) {
                    $set($usersField, static::filteredUserIds($get));

                    return;
                }

                $set($usersField, []);
            });
    }

    public static function usersSelect(string $name = 'user_ids'): UserSearchSelect
    {
        return UserSearchSelect::make($name)
            ->label('Users')
            ->placeholder('Search for users by name or email...')
            ->required()
            ->helperText('Select one or more users matching the filters above, or use “Select all matching users”.')
            ->options(fn (Get $get): array => static::filteredUserOptions($get))
            ->getSearchResultsUsing(fn (string $search, Get $get): array => static::filteredUserOptions($get, $search))
            ->getOptionLabelsUsing(fn (array $values): array => static::labelsForIds($values));
    }

    public static function applyFilters(Builder $query, array $filters, ?string $search = null): Builder
    {
        $userModel = static::userModel();
        $table = (new $userModel)->getTable();

        if (filled($search) && strlen($search) >= 2) {
            $query->where(function (Builder $searchQuery) use ($search, $table): void {
                if (Schema::hasColumn($table, 'first_name') && Schema::hasColumn($table, 'last_name')) {
                    $searchQuery->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                }

                if (Schema::hasColumn($table, 'name')) {
                    $searchQuery->orWhere('name', 'like', "%{$search}%");
                }

                $searchQuery->orWhere('email', 'like', "%{$search}%");
            });
        }

        $provider = static::provider();

        if ($provider instanceof UserAssignmentFilterProvider) {
            $query = $provider->apply($query, $filters);
        }

        return $query;
    }

    /**
     * @return array<int|string, string>
     */
    public static function filteredUserOptions(Get $get, ?string $search = null): array
    {
        $filters = static::filtersFromGet($get);

        $userModel = static::userModel();

        $query = static::applyFilters($userModel::query(), $filters, $search)
            ->limit(50)
            ->get();

        return $query
            ->mapWithKeys(fn (Model $user): array => [$user->getKey() => static::displayLabel($user)])
            ->all();
    }

    /**
     * @return list<int|string>
     */
    public static function filteredUserIds(Get $get): array
    {
        $filters = static::filtersFromGet($get);
        $userModel = static::userModel();

        return static::applyFilters($userModel::query(), $filters)
            ->pluck((new $userModel)->getKeyName())
            ->all();
    }

    /**
     * @param  array<int, int|string>  $values
     * @return array<int|string, string>
     */
    public static function labelsForIds(array $values): array
    {
        $userModel = static::userModel();

        return $userModel::query()
            ->whereIn((new $userModel)->getKeyName(), $values)
            ->get()
            ->mapWithKeys(fn (Model $user): array => [$user->getKey() => static::displayLabel($user)])
            ->all();
    }

    public static function displayLabel(Model $user): string
    {
        $name = $user->getAttribute('name')
            ?? trim(($user->getAttribute('first_name') ?? '') . ' ' . ($user->getAttribute('last_name') ?? ''));

        $email = $user->getAttribute('email');

        if ($name !== '' && $email) {
            return "{$name} ({$email})";
        }

        return $name !== '' ? $name : (string) $email;
    }

    /**
     * @return class-string<Model>
     */
    public static function userModel(): string
    {
        return config('filament-library.user_model', config('auth.providers.users.model', 'App\\Models\\User'));
    }

    public static function provider(): ?UserAssignmentFilterProvider
    {
        $class = config('filament-library.assignment.filter_provider');

        if (! filled($class)) {
            return null;
        }

        $provider = app($class);

        if (! $provider instanceof UserAssignmentFilterProvider) {
            return null;
        }

        return $provider;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function filtersFromGet(Get $get): array
    {
        $provider = static::provider();

        if (! $provider instanceof UserAssignmentFilterProvider) {
            return [];
        }

        return collect($provider->filterKeys())
            ->mapWithKeys(fn (string $key): array => [$key => $get($key)])
            ->all();
    }

    protected static function refreshSelectedUsersIfSelectingAll(Get $get, Set $set, string $usersField): void
    {
        if (! $get('select_all_matching')) {
            return;
        }

        $set($usersField, static::filteredUserIds($get));
    }
}
