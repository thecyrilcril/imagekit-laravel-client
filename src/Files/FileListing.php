<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Files;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Override;
use Traversable;

/**
 * One page of a listing, in the order ImageKit returned it. Files and
 * Folders share the page when the request asked for `type: All`; read
 * `files()` or `folders()` for one kind, or iterate for both.
 *
 * @implements IteratorAggregate<int, File|Folder>
 */
final readonly class FileListing implements Countable, IteratorAggregate
{
    /**
     * @param  list<File|Folder>  $items
     */
    public function __construct(public array $items) {}

    /**
     * @return list<File>
     */
    public function files(): array
    {
        return array_values(array_filter($this->items, static fn (File|Folder $item): bool => $item instanceof File));
    }

    /**
     * @return list<Folder>
     */
    public function folders(): array
    {
        return array_values(array_filter($this->items, static fn (File|Folder $item): bool => $item instanceof Folder));
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    #[Override]
    public function count(): int
    {
        return count($this->items);
    }

    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
