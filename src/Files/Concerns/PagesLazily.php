<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Files\Concerns;

use Generator;
use Illuminate\Support\LazyCollection;
use Thecyrilcril\ImageKitClient\Files\File;
use Thecyrilcril\ImageKitClient\Files\FileListing;
use Thecyrilcril\ImageKitClient\Files\Folder;
use Thecyrilcril\ImageKitClient\Files\ListRequest;

/**
 * The paging rule behind `Files::lazy()`, shared by the real files area and
 * its fake so the two cannot drift: start at the request's `skip`, move by
 * its `limit` (the default page size when unset), stop on the first page
 * shorter than that.
 */
trait PagesLazily
{
    abstract public function list(ListRequest $request): FileListing;

    /**
     * @return LazyCollection<int, File|Folder>
     */
    public function lazy(ListRequest $request): LazyCollection
    {
        return new LazyCollection(function () use ($request): Generator {
            $pageSize = $request->limit ?? ListRequest::DEFAULT_PAGE_SIZE;
            $skip = $request->skip ?? 0;

            do {
                $page = $this->list($request->withPage($pageSize, $skip));

                foreach ($page as $item) {
                    yield $item;
                }

                $skip += $pageSize;
            } while ($page->count() === $pageSize);
        });
    }
}
