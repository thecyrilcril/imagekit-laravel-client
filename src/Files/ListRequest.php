<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Files;

use Thecyrilcril\ImageKitClient\Enums\AssetType;
use Thecyrilcril\ImageKitClient\Enums\FileType;
use Thecyrilcril\ImageKitClient\Enums\SortOrder;
use Thecyrilcril\ImageKitClient\Exceptions\InvalidListRequest;

/**
 * What to list or search. Every property is optional; a bare request lists
 * the first page of files from the whole media library, every folder
 * included, with ImageKit's own defaults.
 *
 * `path` narrows that to one folder level. To search a folder and its
 * sub-folders in one request, put the path inside `searchQuery` instead
 * (`path : "/kitwire/"`); to walk them yourself, list with `type: All` and
 * recurse into each Folder's `folderPath`.
 *
 * When `searchQuery` is present ImageKit ignores `tags`, `type` and `name`:
 * express those inside the query.
 *
 * An empty string, or an empty tag list, means the same as null: nothing is
 * sent, so ImageKit's default applies rather than an empty filter.
 */
final readonly class ListRequest
{
    /**
     * The most items ImageKit returns in one page.
     */
    public const int MAX_LIMIT = 1000;

    /**
     * The page size lazy() uses when the request names none. ImageKit's own
     * default is not documented, and the short-page stop needs a known size.
     */
    public const int DEFAULT_PAGE_SIZE = 100;

    /**
     * @param  list<string>|null  $tags  Files carrying any of these tags
     *
     * @throws InvalidListRequest
     */
    public function __construct(
        public ?int $limit = null,
        public ?int $skip = null,
        public ?string $path = null,
        public ?AssetType $type = null,
        public ?FileType $fileType = null,
        public ?SortOrder $sort = null,
        public ?array $tags = null,
        public ?string $name = null,
        public ?string $searchQuery = null,
    ) {
        if ($this->limit !== null && ($this->limit < 1 || $this->limit > self::MAX_LIMIT)) {
            throw InvalidListRequest::limitOutOfRange($this->limit, self::MAX_LIMIT);
        }

        if ($this->skip !== null && $this->skip < 0) {
            throw InvalidListRequest::negativeSkip($this->skip);
        }
    }

    /**
     * The same filters, for a different page.
     */
    public function withPage(int $limit, int $skip): self
    {
        return new self(
            limit: $limit,
            skip: $skip,
            path: $this->path,
            type: $this->type,
            fileType: $this->fileType,
            sort: $this->sort,
            tags: $this->tags,
            name: $this->name,
            searchQuery: $this->searchQuery,
        );
    }

    /**
     * The query string as ImageKit documents it: the parameter names as-is,
     * enums by value, tags comma-joined, and nothing for a property left
     * null or empty.
     *
     * @return array<string, int|string>
     */
    public function toQuery(): array
    {
        return array_filter([
            'limit' => $this->limit,
            'skip' => $this->skip,
            'path' => $this->path,
            'type' => $this->type?->value,
            'fileType' => $this->fileType?->value,
            'sort' => $this->sort?->value,
            'tags' => $this->tags === null ? null : implode(',', $this->tags),
            'name' => $this->name,
            'searchQuery' => $this->searchQuery,
        ], static fn (int|string|null $value): bool => $value !== null && $value !== '');
    }
}
