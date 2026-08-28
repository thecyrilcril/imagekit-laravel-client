# ImageKit Laravel Client

A Laravel-native client for the [ImageKit](https://imagekit.io) API, built on `Illuminate\Http\Client`. Typed requests, typed results, typed exceptions. `Http::fake()` intercepts every request it makes.

It exists so that [thecyrilcril/laravel-imagekit](https://github.com/thecyrilcril/laravel-imagekit) no longer needs the `imagekit/imagekit` SDK, which pins Guzzle 7 and cannot be installed on Laravel 13 without `-W`. You can also use it on its own.

> **Status:** pre-release. The Client boots, validates its configuration, and `files()->upload()`, `files()->delete()`, `files()->list()`, `files()->lazy()` and `urls()->build()` work end to end.

## Requirements

- PHP 8.3+
- Laravel 12 or 13

## Installation

```bash
composer require thecyrilcril/imagekit-laravel-client
php artisan vendor:publish --tag=imagekit-client-config
```

The package ships [Laravel Boost](https://laravel.com/docs/boost) AI guidelines (`resources/boost/guidelines/core.blade.php`): run `php artisan boost:install` and select this package, and your coding agent learns the rules above without you writing them.

Add your credentials to `.env`:

```env
IMAGEKIT_PUBLIC_KEY=
IMAGEKIT_PRIVATE_KEY=
IMAGEKIT_URL_ENDPOINT=
```

Every config key reads from the environment:

| Key | Env | Default |
|---|---|---|
| `public_key` | `IMAGEKIT_PUBLIC_KEY` | required |
| `private_key` | `IMAGEKIT_PRIVATE_KEY` | required |
| `url_endpoint` | `IMAGEKIT_URL_ENDPOINT` | required |
| `transformation_position` | `IMAGEKIT_TRANSFORMATION_POSITION` | `path` |
| `http.timeout` | `IMAGEKIT_HTTP_TIMEOUT` | `30` seconds |
| `http.retries` | `IMAGEKIT_HTTP_RETRIES` | `0` |

Resolving the Client with a missing credential, an unknown `transformation_position`, or a non-integer `http.*` value throws `Thecyrilcril\ImageKitClient\Exceptions\InvalidConfiguration`. You find out about a bad `.env` at boot, not at the first upload.

### HTTP behaviour

Every request goes through `Illuminate\Http\Client`, so `Http::fake()` intercepts it in your tests. Management calls go to `https://api.imagekit.io/v1`, uploads to `https://upload.imagekit.io/api/v1`, both with HTTP Basic auth (private key as the user, empty password) and `Accept: application/json`.

- `http.timeout` is the number of seconds to wait for a response.
- `http.retries` is the number of extra attempts after a transport error (DNS, refused connection, timeout) or a `5xx`. Retries are immediate.
- A `429` is retried once, after waiting out the `X-RateLimit-Reset` header (milliseconds), and only when `http.retries` is above `0`. The wait goes through `Thecyrilcril\ImageKitClient\Http\Sleeper`, which you can rebind in tests so nothing actually waits.

## Usage

Inject the contract, or use the facade. Both resolve the same singleton.

```php
use Thecyrilcril\ImageKitClient\Contracts\Client;
use Thecyrilcril\ImageKitClient\Facades\ImageKitClient;

final class UploadAvatar
{
    public function __construct(private readonly Client $imageKit) {}

    public function handle(): void
    {
        $this->imageKit->files()->delete('file_id'); // also list(), lazy()
        $this->imageKit->urls();                     // build delivery URLs
    }
}

ImageKitClient::files()->delete('file_id');
ImageKitClient::urls();
```

### Exceptions

Every exception the package throws extends `Thecyrilcril\ImageKitClient\Exceptions\ImageKitClientException`. Catch that for "anything the Client can fail with", or a subclass to tell the failures apart:

| Exception | When | Carries |
|---|---|---|
| `InvalidConfiguration` | A credential is missing or a config value is malformed | — |
| `ImageKitError` (abstract) | ImageKit answered with an error status; parent of the next three | `status`, `imageKitMessage`, `help`; `getMessage()` is `ImageKit responded with HTTP 400: <message>` |
| `RequestFailed` | Any `4xx`/`5xx` other than `404` or `429` (after retries) | as `ImageKitError` |
| `NotFound` | A `404` | as `ImageKitError` |
| `RateLimited` | A `429` and no retry is left | as `ImageKitError`, plus `retryAfterMilliseconds` |
| `TransportError` | ImageKit could not be reached (after retries) | The `ConnectionException` as `getPrevious()` |
| `InvalidListRequest` | A `ListRequest` with a `limit` outside `1–1000` or a negative `skip` | — |
| `UnexpectedResponse` | ImageKit answered `2xx` with a body that is not what the docs promise (not a JSON listing, an asset with no `type`, a required field missing or malformed) | — |
| `InvalidTransformation` | A Transformation key or value the URL builder cannot render | — |
| `InvalidUrlRequest` | A URL request with no source, with both `path` and `src`, or with a signing option that does not fit (`signed` with `src`, `expiresIn` without `signed`, `expiresIn` ≤ 0) | — |
| `InvalidUploadRequest` | An upload request that could never succeed: empty bytes, a data URI without `data:`, a URL that is not `http(s)`, or an empty `fileName` | — |

```php
use Thecyrilcril\ImageKitClient\Exceptions\NotFound;

try {
    ImageKitClient::files()->delete($fileId);
} catch (NotFound) {
    // Already gone: treat as deleted.
}
```

## Uploading files

`files()->upload()` takes an `UploadRequest` and returns an `UploadedFile`. The content comes from one of three `UploadSource`s: raw bytes (sent as the multipart file part), a base64 `data:` URI, or a public `http(s)` URL that ImageKit fetches itself. Every documented upload field is a named argument under its API name; a field left `null` stays off the wire so ImageKit applies its own default.

```php
use Thecyrilcril\ImageKitClient\Enums\ResponseField;
use Thecyrilcril\ImageKitClient\Facades\ImageKitClient;
use Thecyrilcril\ImageKitClient\Files\UploadRequest;
use Thecyrilcril\ImageKitClient\Files\UploadSource;

$uploaded = ImageKitClient::files()->upload(new UploadRequest(
    source: UploadSource::bytes($contents),            // or ::dataUri('data:image/png;base64,…') or ::url('https://…')
    fileName: 'avatar.jpg',
    folder: '/avatars',
    useUniqueFileName: false,
    overwriteFile: true,
    tags: ['avatar', 'user-42'],
    isPrivateFile: false,
    customMetadata: ['userId' => 42],
    responseFields: [ResponseField::Tags, ResponseField::CustomMetadata],
    extensions: [['name' => 'remove-bg', 'options' => ['add_shadow' => true]]],
    checks: "'file.size' < '5MB'",
));

$uploaded->fileId;       // string
$uploaded->url;          // string
$uploaded->width;        // ?int — null for a non-image
$uploaded->tags;         // list<string>
$uploaded->aiTags;       // list<AITag>
$uploaded->versionInfo;  // ?VersionInfo
$uploaded->extensionStatus?->removeBg; // ?ExtensionState: Success, Pending or Failed
$uploaded->duration;     // ?int — the video group: duration, bitRate, audioCodec, videoCodec
```

Wire rules, so nothing is sent in a form ImageKit misreads: booleans go as the words `"true"`/`"false"` (a raw `false` would leave as an empty field); `tags` and `responseFields` are comma-joined; `customMetadata`, `extensions` and `transformation` are JSON. The shapes of those three are ImageKit's own and pass through verbatim — see the [upload API reference](https://imagekit.io/docs/api-reference/upload-file/upload-file) for the keys each accepts.

`UploadedFile` exposes every documented response field, typed. The fields ImageKit only sends when asked for through `responseFields` (`tags`, `customCoordinates`, `isPrivateFile`, `isPublished`, `customMetadata`, `embeddedMetadata`, `metadata`, `selectedFieldsSchema`) read as `null`, or as an empty list or map, when they were not asked for. Fields ImageKit adds later are ignored.

An upload ImageKit rejects throws `RequestFailed` with the status and ImageKit's `message`; an unreachable ImageKit throws `TransportError`; a `2xx` whose body is not the documented shape throws `UnexpectedResponse`. Empty bytes, a data URI without the `data:` prefix, a URL that is not `http(s)`, or an empty `fileName` throw `InvalidUploadRequest` before any request leaves.

## Listing and searching files

`files()->list()` fetches one page of a listing as a `FileListing`; `files()->lazy()` walks every page for you. Both take a `ListRequest` whose properties are exactly the documented query parameters, with enums where ImageKit enumerates.

```php
use Thecyrilcril\ImageKitClient\Enums\AssetType;
use Thecyrilcril\ImageKitClient\Enums\FileType;
use Thecyrilcril\ImageKitClient\Enums\SortOrder;
use Thecyrilcril\ImageKitClient\Facades\ImageKitClient;
use Thecyrilcril\ImageKitClient\Files\File;
use Thecyrilcril\ImageKitClient\Files\Folder;
use Thecyrilcril\ImageKitClient\Files\ListRequest;

$page = ImageKitClient::files()->list(new ListRequest(
    path: '/avatars',              // one folder level; see below for sub-folders
    type: AssetType::All,          // File, FileVersion, Folder, or All (files and folders together)
    fileType: FileType::Image,     // All, Image, NonImage
    sort: SortOrder::CreatedDescending,
    tags: ['hero', 'summer sale'], // sent comma-joined
    name: 'banner.jpg',
    limit: 100,                    // 1–1000
    skip: 0,
));

foreach ($page->files() as $file) {
    $file->fileId; $file->filePath; $file->url; $file->size; $file->tags; $file->createdAt; // …
}

foreach ($page->folders() as $folder) {
    $folder->folderId; $folder->folderPath;
}
```

- `ListRequest` accepts `limit`, `skip`, `path`, `type`, `fileType`, `sort`, `tags`, `name` and `searchQuery`. A property left `null` (or empty) is not sent, so ImageKit's defaults apply. A `limit` outside `1–1000` or a negative `skip` throws `InvalidListRequest` when the request is built.
- `searchQuery` is ImageKit's Lucene-like string (`createdAt > "7d" AND path : "/avatars/"`). When it is present ImageKit ignores `tags`, `type` and `name`; express those inside the query.
- `path` lists one folder level only. To search a folder and its sub-folders in one request, put the path in `searchQuery` (`path : "/avatars/"`). To walk them yourself, list with `type: AssetType::All` and recurse into each `Folder`'s `folderPath` — that is what `imagekit:reconcile` in thecyrilcril/laravel-imagekit does.
- A folder with nothing in it, or a search that matches nothing, is an empty `FileListing` (`isEmpty()`, `count() === 0`), not an exception.

`FileListing` is `Countable` and iterable. `items` holds every entry in ImageKit's order (`File` and `Folder` objects, told apart by class or by `->type`); `files()` and `folders()` return one kind.

`File` carries every documented field, typed: `fileId`, `type` (`AssetType::File` or `FileVersion`), `name`, `filePath`, `url`, `thumbnail`, `fileType` (`image`/`non-image`, kept as a string), `mime`, `size`, `width`, `height`, `hasAlpha`, `tags`, `aiTags` (`AITag` objects: `name`, `confidence`, `source`), `customCoordinates`, `customMetadata`, `description`, `embeddedMetadata`, `selectedFieldsSchema`, `isPrivateFile`, `isPublished`, `versionInfo` (`id`, `name`), `createdAt`, `updatedAt` (`DateTimeImmutable`), and for video `duration`, `bitRate`, `audioCodec`, `videoCodec`. A field ImageKit only sets for some files is `null` when absent; the list and object fields are empty instead. `Folder` carries `folderId`, `name`, `folderPath`, `customMetadata`, `createdAt`, `updatedAt` and `type` (`AssetType::Folder`). Fields this package does not know are ignored; a `2xx` whose body is not a listing, or an asset missing a required field, throws `UnexpectedResponse`.

### Paging

`lazy()` returns a `LazyCollection` of `File|Folder`. Nothing is sent until you consume it; each page is fetched when you reach it. Paging starts at the request's `skip` (default `0`), moves by its `limit` (`ListRequest::DEFAULT_PAGE_SIZE`, 100, when not set), and stops on the first page shorter than that limit.

```php
ImageKitClient::files()
    ->lazy(new ListRequest(path: '/avatars', type: AssetType::All, limit: 500))
    ->filter(fn (File|Folder $item): bool => $item instanceof File)
    ->each(function (File $file): void {
        // …
    });
```

An error on any page (`RequestFailed`, `RateLimited`, `TransportError`, `UnexpectedResponse`) surfaces from the consumer loop.

## Building URLs

`urls()->build()` turns a `UrlRequest` into a delivery URL. Pure string building, no HTTP.

```php
use Thecyrilcril\ImageKitClient\Facades\ImageKitClient;
use Thecyrilcril\ImageKitClient\Urls\UrlRequest;

ImageKitClient::urls()->build(new UrlRequest(
    path: '/avatars/a.jpg',
    transformation: ['width' => 200, 'height' => 200, 'focus' => 'face'],
));
// https://ik.imagekit.io/your_id/tr:w-200,h-200,fo-face/avatars/a.jpg
```

A Transformation is a flat array. Its keys are friendly aliases (table below), ImageKit short codes (`['w' => 200, 'e-bgremove' => true]`), or `raw`, which passes its value through verbatim (no encoding) for syntax the map does not cover (layers, conditionals, a code newer than this package); in a signed URL, percent-encode a `raw` value yourself, since the signature covers the exact bytes. Any other key throws `Thecyrilcril\ImageKitClient\Exceptions\InvalidTransformation`, so a typo in a preset fails loudly instead of emitting a broken URL.

- **Chains**: a list of arrays renders as steps joined with `:`: `[['width' => 400], ['rotation' => 90]]` gives `tr:w-400:rt-90`.
- **Booleans**: spelled out as documented (`lossless => true` gives `lo-true`, `metadata => false` gives `md-false`). Effects take no value when `true` (`grayscale => true` gives `e-grayscale`, `aiRemoveBackground => true` gives `e-bgremove`); give a string to add parameters (`sharpen => 10` gives `e-sharpen-10`, `aiChangeBackground => 'prompt-snow road'` gives `e-changebg-prompt-snow%20road`).
- **Slashes** in a value become `@@` (`defaultImage => '/images/fallback.jpg'` gives `di-images@@fallback.jpg`).
- **Position**: `position: TransformationPosition::Query` puts the Transformation in the query string (`/a.jpg?tr=w-200`) for one request; `transformation_position` in config sets the default.
- **`src`**: pass an absolute URL instead of `path` to transform an image already on an ImageKit endpoint. The Transformation always goes in the query string, after any query the `src` carries.
- **`queryParameters`**: appended to the URL (`['ik-attachment' => true]` gives `?ik-attachment=true`).
- **`signed`**: appends `ik-s`, the HMAC-SHA1 of the URL made with `private_key`, for private files and accounts that restrict unsigned URLs. `expiresIn` (seconds from now) also appends `ik-t`, after which the CDN answers `401`; without it the signature never expires. Signing follows the [current docs](https://imagekit.io/docs/media-delivery-basic-security): the path is percent-encoded first (non-ASCII, spaces; an existing `%` is kept), then everything after the endpoint is signed. Signing needs a `path`: a `src` throws `InvalidUrlRequest`, as does `expiresIn` without `signed` or an `expiresIn` of zero or less.

```php
ImageKitClient::urls()->build(new UrlRequest(
    path: '/private/report.pdf',
    signed: true,
    expiresIn: 300,
));
// https://ik.imagekit.io/your_id/private/report.pdf?ik-t=1700000300&ik-s=…
```

| Alias | Code | Alias | Code | Alias | Code |
|---|---|---|---|---|---|
| `width` | `w` | `defaultImage` | `di` | `colorize` | `e-colorize` |
| `height` | `h` | `named` | `n` | `distort` | `e-distort` |
| `aspectRatio` | `ar` | `radius` | `r` | `aiRemoveBackground` | `e-bgremove` |
| `crop` | `c` | `background` | `bg` | `aiRemoveBackgroundExternal` | `e-removedotbg` |
| `cropMode` | `cm` | `border` | `b` | `aiChangeBackground` | `e-changebg` |
| `focus` | `fo` | `rotation` | `rt` | `aiEdit` | `e-edit` |
| `zoom` | `z` | `flip` | `fl` | `aiDropShadow` | `e-dropshadow` |
| `x`, `y` | `x`, `y` | `blur` | `bl` | `aiRetouch` | `e-retouch` |
| `xCenter`, `yCenter` | `xc`, `yc` | `trim` | `t` | `aiUpscale` | `e-upscale` |
| `dpr` | `dpr` | `opacity` | `o` | `aiVariation` | `e-genvar` |
| `quality` | `q` | `colorReplace` | `cr` | `page` | `pg` |
| `format` | `f` | `contrastStretch` | `e-contrast` | `contentCredentials` | `c2pa` |
| `lossless` | `lo` | `sharpen` | `e-sharpen` | `startOffset` | `so` |
| `progressive` | `pr` | `unsharpMask` | `e-usm` | `endOffset` | `eo` |
| `metadata` | `md` | `grayscale` | `e-grayscale` | `duration` | `du` |
| `colorProfile` | `cp` | `shadow` | `e-shadow` | `videoCodec` | `vc` |
| `density` | `dn` | `gradient` | `e-gradient` | `audioCodec` | `ac` |
| `original` | `orig` | | | `streamingResolutions` | `sr` |

The names `imagekit/imagekit` 4.0.2 accepted (`rotate`, `effectSharpen`, `effectUSM`, `effectContrast`, `effectGray`, `effectShadow`, `effectGradient`, and `'-'` as a value for a bare code) are also accepted, so presets written against it keep rendering the same URL.

## Transformation position

Transformations go in the URL path by default (`/tr:w-200/photo.jpg`). ImageKit's newer SDKs default to the query string (`?tr=w-200`). Both render the same image, but the CDN caches by URL text, so this package keeps the path form to stay byte-identical with URLs already in the wild. Set `transformation_position` to `query` to opt in to the other form.

## Faking the Client in your tests

`ImageKitClient::fake()` swaps the Client in the container for a fake that records uploads, deletions and listings and never sends a request. Anything that injects `Contracts\Client`, and the facade, get the fake from then on.

```php
use Thecyrilcril\ImageKitClient\Facades\ImageKitClient;
use Thecyrilcril\ImageKitClient\Files\UploadRequest;

$fake = ImageKitClient::fake();

// ... run the code under test ...

$fake->assertUploaded('photo.jpg');                                   // by file name
$fake->assertUploaded(fn (UploadRequest $request) => $request->folder === '/avatars'); // or by callback
$fake->assertNotUploaded('draft.jpg');
$fake->assertNothingUploaded();
$fake->assertDeleted('file_123');
$fake->assertListed('/avatars');                                       // by path, or by callback
```

The fake answers as ImageKit would, without HTTP:

- `upload()` returns an `UploadedFile` built from the request: `fileId` is `fake_<n>` for the n-th upload, `name` is the given `fileName` (no unique suffix), `filePath` joins `folder` and `fileName`, `url` and `thumbnailUrl` come from the real URL builder, `size` is the byte count for a bytes source (0 for a data URI or a URL), and `fileType` follows the extension.
- `delete()` records the id and never throws.
- `list()` and `lazy()` answer from the items you seed with `seedListing(File|Folder ...$items)`, keeping the ones ImageKit would return for the request's `path` (that one folder level) and `type` (files by default; `All` is files and folders), paged by `skip` and `limit`. The other filters are ignored; assert on the recorded `ListRequest` instead. Unseeded, every listing is empty.
- `urls()` is the real builder, so a test sees the same URLs as production (credentials must be configured, as for the real Client).

To test your own failure handling, tell the fake to reject uploads: every `upload()` then throws `RequestFailed` (HTTP 500) and the attempt is still recorded.

```php
$fake = ImageKitClient::fake()->failUploads();
```

Combine it with `Http::fake()` and `Http::assertNothingSent()` to prove your code never reaches ImageKit.

## Testing

```bash
composer test           # Pest
composer lint           # Pint
composer analyse        # PHPStan (Larastan, level 7)
composer ci             # Pint --test, PHPStan, Pest with a 100% coverage gate
```

## License

MIT. See [LICENSE.md](LICENSE.md).
