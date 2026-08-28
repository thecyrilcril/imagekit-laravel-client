<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Tests\Support;

use Illuminate\Http\Client\Request;

/**
 * Reads the multipart parts a faked request left with, so a test can say
 * exactly which fields reached the wire and as what text.
 */
final readonly class Multipart
{
    /**
     * Every text field by name; the file part (the one with a filename) is left out.
     *
     * @return array<string, string>
     */
    public static function fields(Request $request): array
    {
        $fields = [];

        foreach (self::parts($request) as $part) {
            if (! isset($part['filename'])) {
                $fields[$part['name']] = $part['contents'];
            }
        }

        return $fields;
    }

    /**
     * Whether a file part (one with a filename) named $name left with the request.
     */
    public static function hasFilePart(Request $request, string $name): bool
    {
        foreach (self::parts($request) as $part) {
            if ($part['name'] === $name && isset($part['filename'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{name: string, contents: string, filename?: string}>
     */
    private static function parts(Request $request): array
    {
        /** @var list<array{name: string, contents: string, filename?: string}> $parts */
        $parts = $request->data();

        return $parts;
    }
}
