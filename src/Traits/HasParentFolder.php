<?php

namespace Tapp\FilamentLibrary\Traits;

trait HasParentFolder
{
    public ?int $parentId = null;

    public function mount(): void
    {
        parent::mount();

        $this->parentId = request()->get('parent');
    }

    protected function getRedirectUrl(): string
    {
        return $this->parentId
            ? static::getResource()::getUrl('index', ['parent' => $this->parentId])
            : static::getResource()::getUrl('index');
    }

    protected function getParentId(): ?int
    {
        return $this->parentId;
    }
}

