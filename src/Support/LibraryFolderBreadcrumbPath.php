<?php

declare(strict_types=1);

namespace Tapp\FilamentLibrary\Support;

use Filament\Facades\Filament;
use Tapp\FilamentLibrary\FilamentLibraryPlugin;
use Tapp\FilamentLibrary\Models\LibraryItem;

final class LibraryFolderBreadcrumbPath
{
    /**
     * @return list<array{id: int, name: string}>
     */
    public static function ancestorsForParentId(?int $parentId): array
    {
        if ($parentId === null) {
            return [];
        }

        $cacheKey = self::cacheKey($parentId);
        $cacheTtl = (int) config('filament-library.cache.breadcrumbs_ttl_seconds', 300);

        /** @var list<array{id: int, name: string}> $path */
        $path = cache()->remember($cacheKey, $cacheTtl, function () use ($parentId): array {
            return self::buildPath($parentId);
        });

        return $path;
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public static function ancestorsForFolder(LibraryItem $folder): array
    {
        $folderId = $folder->getKey();

        if (! is_int($folderId)) {
            return self::buildPathFromFolder($folder);
        }

        $cacheKey = self::cacheKey($folderId);
        $cacheTtl = (int) config('filament-library.cache.breadcrumbs_ttl_seconds', 300);

        /** @var list<array{id: int, name: string}> $path */
        $path = cache()->remember($cacheKey, $cacheTtl, function () use ($folder): array {
            return self::buildPathFromFolder($folder);
        });

        return $path;
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private static function buildPath(int $parentId): array
    {
        /** @var class-string<LibraryItem> $modelClass */
        $modelClass = FilamentLibraryPlugin::libraryItemModelClass();

        $folder = $modelClass::query()->find($parentId);

        if ($folder === null) {
            return [];
        }

        return self::buildPathFromFolder($folder);
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private static function buildPathFromFolder(LibraryItem $folder): array
    {
        $path = [];
        $current = $folder;

        while ($current) {
            $currentId = $current->getKey();

            if (! is_int($currentId)) {
                break;
            }

            array_unshift($path, [
                'id' => $currentId,
                'name' => (string) $current->name,
            ]);

            $current = $current->parent;
        }

        return $path;
    }

    private static function cacheKey(int $folderId): string
    {
        $tenantSegment = 'global';

        if (config('filament-library.tenancy.enabled') && class_exists(Filament::class)) {
            $tenant = Filament::getTenant();

            if ($tenant !== null) {
                $tenantKey = $tenant->getKey();
                $tenantSegment = is_int($tenantKey) || is_string($tenantKey)
                    ? (string) $tenantKey
                    : 'global';
            }
        }

        return "filament-library.breadcrumbs.{$tenantSegment}.{$folderId}";
    }
}
