<?php

namespace Tapp\FilamentLibrary\Forms\Components;

use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;

class UserSearchSelect extends Select
{
    protected string $userModel;

    protected string $nameField = 'first_name';

    protected string $emailField = 'email';

    protected string $searchFields = 'first_name,last_name,email';

    public static function make(?string $name = null): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure();

        return $static;
    }

    public function userModel(string $model): static
    {
        $this->userModel = $model;

        return $this;
    }

    public function nameField(string $field): static
    {
        $this->nameField = $field;

        return $this;
    }

    public function emailField(string $field): static
    {
        $this->emailField = $field;

        return $this;
    }

    public function searchFields(string $fields): static
    {
        $this->searchFields = $fields;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Set default user model if not specified
        if (empty($this->userModel)) {
            $this->userModel = config('auth.providers.users.model', 'App\\Models\\User');
        }

        $this->searchable()
            ->multiple()
            ->preload()
            ->getSearchResultsUsing(function (string $search): array {
                if (strlen($search) < 2) {
                    return [];
                }

                $userModel = $this->userModel;
                $searchFields = explode(',', $this->searchFields);

                $query = $userModel::query();

                foreach ($searchFields as $field) {
                    $query->orWhere(trim($field), 'like', "%{$search}%");
                }

                return $query
                    ->limit(50)
                    ->get()
                    ->mapWithKeys(function (Model $user) {
                        $name = $this->getDisplayName($user);
                        $email = $user->getAttribute($this->emailField);
                        $label = $email ? "{$name} ({$email})" : $name;

                        return [$user->getKey() => $label];
                    })
                    ->toArray();
            })
            ->getOptionLabelsUsing(function (array $values): array {
                $userModel = $this->userModel;

                return $userModel::whereIn('id', $values)
                    ->get()
                    ->mapWithKeys(function (Model $user) {
                        $name = $this->getDisplayName($user);
                        $email = $user->getAttribute($this->emailField);
                        $label = $email ? "{$name} ({$email})" : $name;

                        return [$user->getKey() => $label];
                    })
                    ->toArray();
            });
    }

    protected function getDisplayName(Model $user): string
    {
        // Try to use the name accessor first (if it exists)
        if (method_exists($user, 'getNameAttribute') || $user->getAttribute('name')) {
            return $user->name;
        }

        // Fallback to combining first_name and last_name
        $firstName = $user->getAttribute('first_name') ?? '';
        $lastName = $user->getAttribute('last_name') ?? '';

        return trim("{$firstName} {$lastName}") ?: $user->getAttribute('email') ?? 'Unknown User';
    }
}
