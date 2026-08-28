<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Files;

use Thecyrilcril\ImageKitClient\Enums\UploadSourceKind;
use Thecyrilcril\ImageKitClient\Exceptions\InvalidUploadRequest;

/**
 * Where an upload's content comes from. Build one with the named
 * constructor for the form you hold; the Client picks the wire shape.
 */
final readonly class UploadSource
{
    private function __construct(
        public UploadSourceKind $kind,
        public string $value,
    ) {}

    /**
     * In-memory file content, sent as the multipart file part.
     *
     * @throws InvalidUploadRequest when the content is empty
     */
    public static function bytes(string $contents): self
    {
        if ($contents === '') {
            throw InvalidUploadRequest::emptyBytes();
        }

        return new self(UploadSourceKind::Bytes, $contents);
    }

    /**
     * Content already base64-encoded as a `data:` URI, sent as text for
     * ImageKit to decode; nothing is re-encoded on the way.
     *
     * @throws InvalidUploadRequest when the string does not start with `data:`
     */
    public static function dataUri(string $uri): self
    {
        if (! str_starts_with($uri, 'data:')) {
            throw InvalidUploadRequest::notADataUri();
        }

        return new self(UploadSourceKind::DataUri, $uri);
    }

    /**
     * A public HTTP(S) URL that ImageKit fetches itself, so this server
     * never holds the bytes. ImageKit answers 400 if the URL does not
     * respond within 8 seconds.
     *
     * @throws InvalidUploadRequest when the string is not an http(s) URL
     */
    public static function url(string $url): self
    {
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            throw InvalidUploadRequest::notAUrl($url);
        }

        return new self(UploadSourceKind::Url, $url);
    }
}
