<?php

namespace Tapp\FilamentLibrary\Services;

use InvalidArgumentException;
use Tapp\FilamentLibrary\Enums\LibraryPublicationStatus;
use Tapp\FilamentLibrary\FilamentLibraryPlugin;
use Tapp\FilamentLibrary\Models\LibraryItem;

class LibraryImporter
{
    /**
     * Import a CSV manifest. Rows land as draft or pending. Published is never applied from the file.
     */
    public function importCsv(string $path, int $userId, LibraryPublicationStatus $defaultStatus = LibraryPublicationStatus::Pending): LibraryImportResult
    {
        if (! is_file($path)) {
            return new LibraryImportResult(items: [], errors: ["Manifest [{$path}] was not found."]);
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return new LibraryImportResult(items: [], errors: ["Manifest [{$path}] could not be read."]);
        }

        $header = fgetcsv($handle, 0, ',', '"', '');

        if ($header === false || $header === [null]) {
            fclose($handle);

            return new LibraryImportResult(items: [], errors: ['Manifest has no header row.']);
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]) ?? (string) $header[0];
        $map = $this->headerMap($header);

        if (! isset($map['name'])) {
            fclose($handle);

            return new LibraryImportResult(items: [], errors: ['Manifest must include a name column.']);
        }

        $items = [];
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $line++;

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $record = [];

            foreach ($map as $field => $index) {
                $record[$field] = $row[$index] ?? null;
            }

            try {
                $items[] = $this->importRow($record, $userId, $defaultStatus);
            } catch (InvalidArgumentException $exception) {
                $errors[] = "Row {$line}: {$exception->getMessage()}";
            }
        }

        fclose($handle);

        return new LibraryImportResult($items, $errors);
    }

    /**
     * Create one library item from a manifest row.
     *
     * The item is always draft or pending. A published value in the row is ignored.
     *
     * @param  array<string, mixed>  $row
     */
    public function importRow(array $row, int $userId, LibraryPublicationStatus $defaultStatus = LibraryPublicationStatus::Pending): LibraryItem
    {
        $name = trim((string) ($row['name'] ?? ''));

        if ($name === '') {
            throw new InvalidArgumentException('Name is required.');
        }

        $type = strtolower(trim((string) ($row['type'] ?? 'file')));

        if ($type === '') {
            $type = 'file';
        }

        if (! in_array($type, ['file', 'folder', 'link'], true)) {
            throw new InvalidArgumentException("Type [{$type}] is not supported.");
        }

        $libraryArea = $this->nullableString($row['library_area'] ?? $row['folder'] ?? null);
        $committee = $this->nullableString($row['committee'] ?? null);
        $this->assertCommitteeIsAllowed($committee);

        $status = $this->resolveImportStatus(
            isset($row['publication_status']) ? (string) $row['publication_status'] : null,
            $defaultStatus,
        );

        $model = FilamentLibraryPlugin::libraryItemModelClass();
        $parentId = null;

        if ($libraryArea !== null && ! ($type === 'folder' && $name === $libraryArea)) {
            $parentId = $this->areaFolderId($model, $libraryArea, $userId);
        }

        /** @var LibraryItem $item */
        $item = $model::query()->create([
            'name' => $name,
            'type' => $type,
            'parent_id' => $parentId,
            'created_by' => $userId,
            'updated_by' => $userId,
            'general_access' => 'private',
            'external_url' => $type === 'link' ? $this->nullableString($row['external_url'] ?? null) : null,
            'firm_path' => $this->nullableString($row['firm_path'] ?? null),
            'library_area' => $libraryArea,
            'committee' => $committee,
            'project_tags' => LibraryItem::normalizeProjectTags($row['project_tags'] ?? null),
            'publication_status' => $status,
        ]);

        return $item->refresh();
    }

    public function resolveImportStatus(?string $requested, LibraryPublicationStatus $default): LibraryPublicationStatus
    {
        if (! in_array($default, [LibraryPublicationStatus::Draft, LibraryPublicationStatus::Pending], true)) {
            $default = LibraryPublicationStatus::Pending;
        }

        return match (strtolower(trim((string) $requested))) {
            '' => $default,
            'draft' => LibraryPublicationStatus::Draft,
            'pending' => LibraryPublicationStatus::Pending,
            default => $default,
        };
    }

    /**
     * @param  list<string|null>  $header
     * @return array<string, int>
     */
    protected function headerMap(array $header): array
    {
        $aliases = [
            'name' => ['name', 'title'],
            'firm_path' => ['firm_path', 'firm path', 'path'],
            'library_area' => ['library_area', 'library area', 'folder', 'area'],
            'committee' => ['committee'],
            'project_tags' => ['project_tags', 'project tags', 'tags'],
            'type' => ['type'],
            'external_url' => ['external_url', 'url'],
            'publication_status' => ['publication_status', 'status'],
        ];

        $normalized = [];

        foreach ($header as $index => $column) {
            $normalized[strtolower(trim((string) $column))] = $index;
        }

        $map = [];

        foreach ($aliases as $field => $names) {
            foreach ($names as $name) {
                if (array_key_exists($name, $normalized)) {
                    $map[$field] = $normalized[$name];

                    break;
                }
            }
        }

        return $map;
    }

    /**
     * @param  list<string|null>  $row
     */
    protected function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function assertCommitteeIsAllowed(?string $committee): void
    {
        if ($committee === null) {
            return;
        }

        $allowed = config('filament-library.publication.committees', []);

        if (! is_array($allowed) || $allowed === []) {
            return;
        }

        if (! in_array($committee, $allowed, true)) {
            throw new InvalidArgumentException("Committee [{$committee}] is not configured.");
        }
    }

    /**
     * @param  class-string<LibraryItem>  $model
     */
    protected function areaFolderId(string $model, string $libraryArea, int $userId): int
    {
        $folder = $model::query()
            ->where('type', 'folder')
            ->where('library_area', $libraryArea)
            ->whereNull('parent_id')
            ->first();

        if ($folder) {
            return (int) $folder->getKey();
        }

        $folder = $model::query()->create([
            'name' => $libraryArea,
            'type' => 'folder',
            'parent_id' => null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'general_access' => 'private',
            'library_area' => $libraryArea,
            'publication_status' => LibraryPublicationStatus::Published,
        ]);

        return (int) $folder->getKey();
    }
}
