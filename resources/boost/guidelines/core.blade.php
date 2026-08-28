## ImageKit Laravel Client

`thecyrilcril/imagekit-laravel-client` is a Laravel-native client for the ImageKit API, built on `Illuminate\Http\Client`. It uploads, deletes, lists and searches files, and builds delivery URLs with transformations. Requests, results and errors are typed.

### Rules

- Go through the Client for every ImageKit call. Inject `Thecyrilcril\ImageKitClient\Contracts\Client` or use the `Thecyrilcril\ImageKitClient\Facades\ImageKitClient` facade. Never call `api.imagekit.io` or `upload.imagekit.io` yourself with `Http::`, Guzzle or curl.
- Never add `imagekit/imagekit` to `composer.json`. It pins Guzzle 7 and cannot be installed on Laravel 13. This Client replaces it.
- The Client has two areas. `ImageKitClient::files()` has `upload(UploadRequest)`, `delete(string $fileId)`, `list(ListRequest)` and `lazy(ListRequest)`. `ImageKitClient::urls()` has `build(UrlRequest)`. Nothing else exists in 0.1.0.
- Credentials live in `config/imagekit-client.php` and come from `.env`: `IMAGEKIT_PUBLIC_KEY`, `IMAGEKIT_PRIVATE_KEY`, `IMAGEKIT_URL_ENDPOINT`. Publish the file with `php artisan vendor:publish --tag=imagekit-client-config`. Do not put credentials in another config file or hard-code them.
- A missing credential throws `Thecyrilcril\ImageKitClient\Exceptions\InvalidConfiguration` when the Client is first resolved, not at the first request.
- Every error is a typed exception under `Thecyrilcril\ImageKitClient\Exceptions\ImageKitClientException`. Catch a subclass to react to one case: `NotFound` (404), `RateLimited` (429; retried once only when `http.retries` is above 0), `RequestFailed` (any other 4xx or 5xx), `TransportError` (ImageKit could not be reached), `UnexpectedResponse` (2xx with a body that is not the documented shape). A request that could never succeed throws `InvalidUploadRequest`, `InvalidListRequest` or `InvalidUrlRequest` before anything is sent. Never inspect the HTTP response yourself.
- A Transformation (a laravel-imagekit Preset is one) is a flat array with friendly keys: `['width' => 200, 'height' => 200, 'focus' => 'face']` renders `tr:w-200,h-200,fo-face`. ImageKit short codes (`w`, `fo`) and `raw` are also accepted as keys. Any other key throws `Thecyrilcril\ImageKitClient\Exceptions\InvalidTransformation`. Do not invent keys; do not build the `tr:` string by hand. A chain is a list of arrays.
- Transformations go in the URL path by default (`/tr:w-200/photo.jpg`), not the query string. ImageKit's CDN caches by URL text, so changing `transformation_position` on a live project makes every cached image a miss. Leave it on `path` unless the user asks.
- In tests, call `ImageKitClient::fake()` and assert on it. Do not use `Http::fake()` to stub ImageKit; the fake records uploads, deletions and listings and never sends a request. The fake builds real URLs, so the three `IMAGEKIT_*` keys must be set in `phpunit.xml` (dummy values work) or `fake()` throws `InvalidConfiguration`.

### Examples

@verbatim
<code-snippet name="Upload a file from bytes" lang="php">
use Thecyrilcril\ImageKitClient\Facades\ImageKitClient;
use Thecyrilcril\ImageKitClient\Files\UploadRequest;
use Thecyrilcril\ImageKitClient\Files\UploadSource;

$uploaded = ImageKitClient::files()->upload(new UploadRequest(
    source: UploadSource::bytes($contents), // or UploadSource::dataUri(...) / UploadSource::url(...)
    fileName: 'avatar.jpg',
    folder: '/avatars',
));

$uploaded->fileId; $uploaded->url;
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Build a delivery URL with a transformation" lang="php">
use Thecyrilcril\ImageKitClient\Facades\ImageKitClient;
use Thecyrilcril\ImageKitClient\Urls\UrlRequest;

ImageKitClient::urls()->build(new UrlRequest(
    path: '/avatars/a.jpg',
    transformation: ['width' => 200, 'height' => 200, 'focus' => 'face'],
));
// https://ik.imagekit.io/your_id/tr:w-200,h-200,fo-face/avatars/a.jpg
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Treat a missing file as already deleted" lang="php">
use Thecyrilcril\ImageKitClient\Exceptions\NotFound;
use Thecyrilcril\ImageKitClient\Facades\ImageKitClient;

try {
    ImageKitClient::files()->delete($fileId);
} catch (NotFound) {
    // Already gone.
}
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="phpunit.xml keys the fake needs" lang="xml">
<env name="IMAGEKIT_PUBLIC_KEY" value="public_test"/>
<env name="IMAGEKIT_PRIVATE_KEY" value="private_test"/>
<env name="IMAGEKIT_URL_ENDPOINT" value="https://ik.imagekit.io/test"/>
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Fake the Client in a test" lang="php">
use Thecyrilcril\ImageKitClient\Facades\ImageKitClient;

$fake = ImageKitClient::fake();

// ... run the code under test ...

$fake->assertUploaded('avatar.jpg');
$fake->assertDeleted('file_123');
$fake->assertNothingUploaded();
</code-snippet>
@endverbatim
