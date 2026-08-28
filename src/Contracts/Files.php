<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Contracts;

use Illuminate\Support\LazyCollection;
use Thecyrilcril\ImageKitClient\Exceptions\NotFound;
use Thecyrilcril\ImageKitClient\Exceptions\RateLimited;
use Thecyrilcril\ImageKitClient\Exceptions\RequestFailed;
use Thecyrilcril\ImageKitClient\Exceptions\TransportError;
use Thecyrilcril\ImageKitClient\Exceptions\UnexpectedResponse;
use Thecyrilcril\ImageKitClient\Files\File;
use Thecyrilcril\ImageKitClient\Files\FileListing;
use Thecyrilcril\ImageKitClient\Files\Folder;
use Thecyrilcril\ImageKitClient\Files\ListRequest;

/**
 * The files area of the ImageKit API: upload, delete, list and search.
 *
 * Operations land one at a time, each with its own typed request and result;
 * this interface is the stable seam they attach to.
 */
interface Files
{
    /**
     * Permanently delete one file by its ImageKit file id.
     *
     * @throws NotFound when ImageKit has no file with that id
     * @throws RateLimited when ImageKit throttled the request and retries are exhausted
     * @throws RequestFailed when ImageKit rejected the request for any other reason
     * @throws TransportError when ImageKit could not be reached
     */
    public function delete(string $fileId): void;

    /**
     * List or search one page of files (and folders, with `type: All` or
     * `type: Folder`).
     * A folder with nothing in it, or a search that matches nothing, is an
     * empty listing, not an error.
     *
     * @throws RateLimited when ImageKit throttled the request and retries are exhausted
     * @throws RequestFailed when ImageKit rejected the request
     * @throws TransportError when ImageKit could not be reached
     * @throws UnexpectedResponse when ImageKit answered 2xx with a body that is not a listing
     */
    public function list(ListRequest $request): FileListing;

    /**
     * Every item the request matches, fetched a page at a time as the
     * collection is consumed. Paging starts at the request's `skip`, moves
     * by its `limit` (100 when not set), and stops on the first page shorter
     * than that.
     *
     * @return LazyCollection<int, File|Folder>
     *
     * @throws RateLimited when ImageKit throttled a page and retries are exhausted
     * @throws RequestFailed when ImageKit rejected a page
     * @throws TransportError when ImageKit could not be reached
     * @throws UnexpectedResponse when a page's body is not a listing
     */
    public function lazy(ListRequest $request): LazyCollection;
}
