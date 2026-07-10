<?php

namespace Tapp\FilamentLibrary\Support;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Tapp\FilamentLibrary\Forms\Components\UserSearchSelect;

class UserAssignmentFilters
{
    /**
     * @return array<int, Field>
     */
    public static function schema(string $usersField = 'user_ids'): array
    {
        return [
            ...static::filterFields(),
            static::usersSelect($usersField),
        ];
    }

    /**
     * @return array<int, Field>
     */
    public static function filterFields(): array
    {
        $fields = [];

        if (static::communityFilterEnabled()) {
            $fields[] = Select::make('community_id')
                ->label('Community')
                ->options(fn (): array => static::communityOptions())
                ->searchable()
                ->preload()
                ->live()
                ->placeholder('All communities');
        }

        if (static::roleFilterEnabled()) {
            $fields[] = Select::make('role_name')
                ->label('User Level')
                ->options(fn (): array => static::roleOptions())
                ->searchable()
                ->preload()
                ->live()
                ->placeholder('All user levels');
        }

        if (static::signupDateFilterEnabled()) {
            $fields[] = DatePicker::make('signed_up_from')
                ->label('Signed Up From')
                ->live()
                ->native(false);

            $fields[] = DatePicker::make('signed_up_until')
                ->label('Signed Up Until')
                ->live()
                ->native(false);
        }

        return $fields;
    }

    public static function usersSelect(string $name = 'user_ids'): UserSearchSelect
    {
        return UserSearchSelect::make($name)
            ->label('Users')
            ->placeholder('Search for users by name or email...')
            ->required()
            ->helperText('Select one or more users matching the filters above.')
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

        if (static::communityFilterEnabled() && filled($filters['community_id'] ?? null)) {
            $foreignKey = config('filament-library.user_filters.community.user_foreign_key', 'community_id');
            $query->where($foreignKey, $filters['community_id']);
        }

        if (static::roleFilterEnabled() && filled($filters['role_name'] ?? null)) {
            $query->whereHas('roles', function (Builder $roleQuery) use ($filters): void {
                $roleQuery->where('name', $filters['role_name']);
            });
        }

        $signupColumn = config('filament-library.user_filters.signup_date.column', 'created_at');

        if (static::signupDateFilterEnabled() && filled($filters['signed_up_from'] ?? null)) {
            $query->whereDate($signupColumn, '>=', $filters['signed_up_from']);
        }

        if (static::signupDateFilterEnabled() && filled($filters['signed_up_until'] ?? null)) {
            $query->whereDate($signupColumn, '<=', $filters['signed_up_until']);
        }

        return $query;
    }

    /**
     * @return array<int|string, string>
     */
    public static function filteredUserOptions(Get $get, ?string $search = null): array
    {
        $filters = [
            'community_id' => $get('community_id'),
            'role_name' => $get('role_name'),
            'signed_up_from' => $get('signed_up_from'),
            'signed_up_until' => $get('signed_up_until'),
        ];

        $userModel = static::userModel();

        $query = static::applyFilters($userModel::query(), $filters, $search)
            ->limit(50)
            ->get();

        return $query
            ->mapWithKeys(fn (Model $user): array => [$user->getKey() => static::displayLabel($user)])
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

    public static function communityFilterEnabled(): bool
    {
        return (bool) config('filament-library.user_filters.community.enabled', false)
            && filled(config('filament-library.user_filters.community.model'));
    }

    public static function roleFilterEnabled(): bool
    {
        return (bool) config('filament-library.user_filters.role.enabled', false)
            && filled(config('filament-library.user_filters.role.model'));
    }

    public static function signupDateFilterEnabled(): bool
    {
        return (bool) config('filament-library.user_filters.signup_date.enabled', true);
    }

    /**
     * @return array<int|string, string>
     */
    protected static function communityOptions(): array
    {
        /** @var class-string<Model> $model */
        $model = config('filament-library.user_filters.community.model');
        $title = config('filament-library.user_filters.community.title_attribute', 'name');

        return $model::query()
            ->orderBy($title)
            ->pluck($title, (new $model)->getKeyName())
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected static function roleOptions(): array
    {
        /** @var class-string<Model> $model */
        $model = config('filament-library.user_filters.role.model');
        $title = config('filament-library.user_filters.role.title_attribute', 'name');

        return $model::query()
            ->orderBy($title)
            ->pluck($title, $title)
            ->all();
    }
}
