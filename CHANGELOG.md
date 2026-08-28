# Changelog

All notable changes to this package are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the package follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- Laravel Boost AI guidelines at `resources/boost/guidelines/core.blade.php`. `php artisan boost:install` offers this package and adds its rules (go through the Client, the two areas, config and env keys, the exception tree, flat Transformation arrays with an unknown key throwing, path position by default, `ImageKitClient::fake()` in tests, never `imagekit/imagekit`) to the agent's guideline file.

## [0.1.0] - 2026-08-28

### Added

- Package scaffold: service provider, `config/imagekit-client.php` with env-var defaults for `public_key`, `private_key`, `url_endpoint`, `transformation_position`, `http.timeout` and `http.retries`, the `ImageKitClient` facade, and the `Client` contract exposing `files()` and `urls()`.
- `Configuration` value object, built on first resolve of the Client. A missing credential, an unknown transformation position, or a non-integer `http.*` value throws `InvalidConfiguration`.
- `ImageKitClientException` as the base of every exception this package throws.
- HTTP core: one `Connection` on `Illuminate\Http\Client` that applies the base URLs (`api.imagekit.io/v1`, `upload.imagekit.io/api/v1`), Basic auth with the private key, `http.timeout`, and the retry policy: `http.retries` extra attempts on transport errors and `5xx`; a `429` is retried once after waiting out `X-RateLimit-Reset` (milliseconds) through the injectable `Http\Sleeper`, only when `http.retries` is above 0.
- Exception tree: abstract `ImageKitError` (status, ImageKit `message`, `help`) with `RequestFailed`, `NotFound` and `RateLimited` (`retryAfterMilliseconds`) under it, and `TransportError` (wraps the connection exception).
- `files()->delete(string $fileId)`: `DELETE /v1/files/{fileId}`; a `404` throws `NotFound`.
- `files()->upload(UploadRequest)`: `POST` multipart to `upload.imagekit.io/api/v1/files/upload` from raw bytes (the multipart file part), a base64 `data:` URI, or a public URL (both as the `file` text field), with every documented request field as a named argument. Wire rules: booleans as `"true"`/`"false"`, `tags` and `responseFields` comma-joined, `customMetadata`/`extensions`/`transformation` as JSON, null fields omitted. Returns a typed `UploadedFile` with every documented response field, including `AITags`, `versionInfo`, `extensionStatus` and the video group; unknown fields are ignored. `InvalidUploadRequest` for an upload that could never succeed; `UnexpectedResponse` for a `2xx` body that is not the documented shape.
- `files()->list(ListRequest)`: `GET /v1/files` with the documented query parameters `limit`, `skip`, `path`, `type` (`AssetType`), `fileType` (`FileType`), `sort` (`SortOrder`), `tags` (comma-joined), `name` and `searchQuery`; a property left null is not sent. Returns a `FileListing` of typed `File` (every documented field, `type` File or FileVersion) and `Folder` objects, told apart by class; an empty result is an empty listing. A `limit` outside 1–1000 or a negative `skip` throws `InvalidListRequest`.
- `files()->lazy(ListRequest)`: a `LazyCollection` that fetches page after page from the request's `skip` by its `limit` (100 when unset) and stops on the first short page.
- `UnexpectedResponse`: thrown when ImageKit answers `2xx` with a body that is not what the docs promise (not a JSON listing, an asset with no `type`, a required field missing or malformed).
- `urls()->build(UrlRequest)`: builds a delivery URL from a `path` (or an absolute `src`) and a Transformation. Friendly aliases for every documented transformation code, short codes and `raw` accepted as keys, chains joined with `:`, `/` in values rendered as `@@`, path position by default with query position per request or via config, and extra query parameters. An unknown key throws `InvalidTransformation`; a request with no source, or two, throws `InvalidUrlRequest`.
- Signed URLs: `UrlRequest` takes `signed` and an optional `expiresIn` (seconds). Signing follows the current ImageKit docs: the path is percent-encoded, the trailing-slashed endpoint is stripped, the expiry (or `9999999999`) is appended, and the HMAC-SHA1 with `private_key` goes on as `ik-s`, preceded by `ik-t` when an expiry was given. "Now" comes from the injectable `Time\Clock`. Signing a `src`, an `expiresIn` without `signed`, or an `expiresIn` of zero or less throws `InvalidUrlRequest`.
- `ImageKitClient::fake()`: swaps the Client in the container for `Testing\ClientFake`, which records uploads, deletions and listings, answers with synthetic typed results (`UploadedFile` built from the request, listings from `seedListing(File|Folder ...)` narrowed by `path` and `type` and paged by `skip`/`limit`), builds URLs through the real builder, and offers `assertUploaded`, `assertNotUploaded`, `assertNothingUploaded`, `assertDeleted` and `assertListed`. `failUploads()` makes every upload throw `RequestFailed`.
- Tooling: Pest, Pint, PHPStan (Larastan, level 7), `composer ci` with a 100% coverage gate, and a GitHub Actions matrix over PHP 8.3–8.5 × Laravel 12–13.
