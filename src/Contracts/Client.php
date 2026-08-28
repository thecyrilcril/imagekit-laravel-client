<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Contracts;

/**
 * The Client: the single entry point to the ImageKit API. Bound as a
 * singleton by the service provider and resolved by the ImageKitClient
 * facade. Type-hint this, never the concrete class.
 *
 * Each API area is its own small interface so it can be read, and faked, on
 * its own. Later areas (folders, cache, custom metadata) add methods here
 * without changing the existing ones.
 */
interface Client
{
    /**
     * Upload, delete, list and search files.
     */
    public function files(): Files;

    /**
     * Build delivery URLs with Transformations, optionally signed.
     */
    public function urls(): Urls;
}
