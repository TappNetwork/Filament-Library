<?php

namespace Tapp\FilamentLibrary\Forms\Components;

use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class UserSearchSelect extends Select
{
    protected string $userModel = 'App\\Models\\User';

    protected string $nameField = 'name';

    protected string $emailField = 'email';

    protected string $searchFields = 'name,email';

    public static function make(string $name): static
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
                        $name = $user->getAttribute($this->nameField);
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
                        $name = $user->getAttribute($this->nameField);
                        $email = $user->getAttribute($this->emailField);
                        $label = $email ? "{$name} ({$email})" : $name;

                        return [$user->getKey() => $label];
                    })
                    ->toArray();
            });
    }
}
