<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Contracts;

use Thecyrilcril\ImageKitClient\Exceptions\NotFound;
use Thecyrilcril\ImageKitClient\Exceptions\RateLimited;
use Thecyrilcril\ImageKitClient\Exceptions\RequestFailed;
use Thecyrilcril\ImageKitClient\Exceptions\TransportError;

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
}
