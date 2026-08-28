# ImageKit Laravel Client

A Laravel-native client for the [ImageKit](https://imagekit.io) API, built on `Illuminate\Http\Client`. Typed requests, typed results, typed exceptions. `Http::fake()` intercepts every request it makes.

It exists so that [thecyrilcril/laravel-imagekit](https://github.com/thecyrilcril/laravel-imagekit) no longer needs the `imagekit/imagekit` SDK, which pins Guzzle 7 and cannot be installed on Laravel 13 without `-W`. You can also use it on its own.

> **Status:** pre-release. The Client boots, validates its configuration, and `files()->delete()` and `urls()->build()` work end to end. Upload and list/search land in `0.1.0`.

## Requirements

- PHP 8.3+
- Laravel 12 or 13

## Installation

```bash
composer require thecyrilcril/imagekit-laravel-client
php artisan vendor:publish --tag=imagekit-client-config
```

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
        $this->imageKit->files()->delete('file_id'); // upload, list, search follow
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
| `RequestFailed` | ImageKit answered with a `4xx`/`5xx` other than `404` or `429` (after retries) | `status`, `help`, ImageKit's `message` in `getMessage()` |
| `NotFound` | ImageKit answered `404` | ImageKit's `message` |
| `RateLimited` | ImageKit answered `429` and no retry is left | `retryAfterMilliseconds` |
| `TransportError` | ImageKit could not be reached (after retries) | The `ConnectionException` as `getPrevious()` |
| `InvalidTransformation` | A Transformation key or value the URL builder cannot render | — |
| `InvalidUrlRequest` | A URL request with no source, or with both `path` and `src` | — |

```php
use Thecyrilcril\ImageKitClient\Exceptions\NotFound;

try {
    ImageKitClient::files()->delete($fileId);
} catch (NotFound) {
    // Already gone: treat as deleted.
}
```

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

A Transformation is a flat array. Its keys are friendly aliases (table below), ImageKit short codes (`['w' => 200, 'e-bgremove' => true]`), or `raw`, which passes its value through verbatim (no encoding) for syntax the map does not cover (layers, conditionals, a code newer than this package). Any other key throws `Thecyrilcril\ImageKitClient\Exceptions\InvalidTransformation`, so a typo in a preset fails loudly instead of emitting a broken URL.

- **Chains**: a list of arrays renders as steps joined with `:`: `[['width' => 400], ['rotation' => 90]]` gives `tr:w-400:rt-90`.
- **Booleans**: spelled out as documented (`lossless => true` gives `lo-true`, `metadata => false` gives `md-false`). Effects take no value when `true` (`grayscale => true` gives `e-grayscale`, `aiRemoveBackground => true` gives `e-bgremove`); give a string to add parameters (`sharpen => 10` gives `e-sharpen-10`, `aiChangeBackground => 'prompt-snow road'` gives `e-changebg-prompt-snow%20road`).
- **Slashes** in a value become `@@` (`defaultImage => '/images/fallback.jpg'` gives `di-images@@fallback.jpg`).
- **Position**: `position: TransformationPosition::Query` puts the Transformation in the query string (`/a.jpg?tr=w-200`) for one request; `transformation_position` in config sets the default.
- **`src`**: pass an absolute URL instead of `path` to transform an image already on an ImageKit endpoint. The Transformation always goes in the query string, after any query the `src` carries.
- **`queryParameters`**: appended to the URL (`['ik-attachment' => true]` gives `?ik-attachment=true`).

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

## Testing

```bash
composer test           # Pest
composer lint           # Pint
composer analyse        # PHPStan (Larastan, level 7)
composer ci             # Pint --test, PHPStan, Pest with a 100% coverage gate
```

## License

MIT. See [LICENSE.md](LICENSE.md).
