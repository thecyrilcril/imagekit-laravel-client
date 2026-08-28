# ImageKit Laravel Client

A Laravel-native client for the [ImageKit](https://imagekit.io) API, built on `Illuminate\Http\Client`. Typed requests, typed results, typed exceptions. `Http::fake()` intercepts every request it makes.

It exists so that [thecyrilcril/laravel-imagekit](https://github.com/thecyrilcril/laravel-imagekit) no longer needs the `imagekit/imagekit` SDK, which pins Guzzle 7 and cannot be installed on Laravel 13 without `-W`. You can also use it on its own.

> **Status:** scaffold. The Client boots, validates its configuration and exposes `files()` and `urls()`. Upload, delete, list/search and URL building land in `0.1.0`.

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
        $this->imageKit->files(); // upload, delete, list, search
        $this->imageKit->urls();  // build delivery URLs
    }
}

ImageKitClient::files();
ImageKitClient::urls();
```

Every exception the package throws extends `Thecyrilcril\ImageKitClient\Exceptions\ImageKitClientException`.

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
