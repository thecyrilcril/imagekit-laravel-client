<?php

declare(strict_types=1);

return [
    // Credentials from the ImageKit dashboard (Developer options > API keys).
    // All three are required; resolving the Client without one throws
    // Thecyrilcril\ImageKitClient\Exceptions\InvalidConfiguration.
    'public_key' => env('IMAGEKIT_PUBLIC_KEY'),
    'private_key' => env('IMAGEKIT_PRIVATE_KEY'),
    'url_endpoint' => env('IMAGEKIT_URL_ENDPOINT'),

    // Where transformations go in a built URL: "path" (/tr:w-200/a.jpg) or
    // "query" (/a.jpg?tr=w-200). Both render the same image, but ImageKit's
    // CDN caches by URL text, so changing this invalidates every cached asset.
    'transformation_position' => env('IMAGEKIT_TRANSFORMATION_POSITION', 'path'),

    'http' => [
        // Seconds to wait for a response before giving up.
        'timeout' => env('IMAGEKIT_HTTP_TIMEOUT', 30),

        // Extra attempts after a transport error or a 5xx. 0 disables retries;
        // a 429 is only retried (after the X-RateLimit-Reset wait) when > 0.
        'retries' => env('IMAGEKIT_HTTP_RETRIES', 0),
    ],
];
