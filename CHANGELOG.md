# Changelog

All notable changes to this package are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the package follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- Package scaffold: service provider, `config/imagekit-client.php` with env-var defaults for `public_key`, `private_key`, `url_endpoint`, `transformation_position`, `http.timeout` and `http.retries`, the `ImageKitClient` facade, and the `Client` contract exposing `files()` and `urls()`.
- `Configuration` value object, built on first resolve of the Client. A missing credential, an unknown transformation position, or a non-integer `http.*` value throws `InvalidConfiguration`.
- `ImageKitClientException` as the base of every exception this package throws.
- HTTP core: one `Connection` on `Illuminate\Http\Client` that applies the base URLs (`api.imagekit.io/v1`, `upload.imagekit.io/api/v1`), Basic auth with the private key, `http.timeout`, and the retry policy: `http.retries` extra attempts on transport errors and `5xx`; a `429` is retried once after waiting out `X-RateLimit-Reset` (milliseconds) through the injectable `Http\Sleeper`, only when `http.retries` is above 0.
- Exception tree: abstract `ImageKitError` (status, ImageKit `message`, `help`) with `RequestFailed`, `NotFound` and `RateLimited` (`retryAfterMilliseconds`) under it, and `TransportError` (wraps the connection exception).
- `files()->delete(string $fileId)`: `DELETE /v1/files/{fileId}`; a `404` throws `NotFound`.
- `files()->list(ListRequest)`: `GET /v1/files` with the documented query parameters `limit`, `skip`, `path`, `type` (`AssetType`), `fileType` (`FileType`), `sort` (`SortOrder`), `tags` (comma-joined), `name` and `searchQuery`; a property left null is not sent. Returns a `FileListing` of typed `File` (every documented field, `type` File or FileVersion) and `Folder` objects, told apart by class; an empty result is an empty listing. A `limit` outside 1–1000 or a negative `skip` throws `InvalidListRequest`.
- `files()->lazy(ListRequest)`: a `LazyCollection` that fetches page after page from the request's `skip` by its `limit` (100 when unset) and stops on the first short page.
- `UnexpectedResponse`: thrown when ImageKit answers `2xx` with a body that is not what the docs promise (not a JSON listing, an asset with no `type`, a required field missing or malformed).
- `urls()->build(UrlRequest)`: builds a delivery URL from a `path` (or an absolute `src`) and a Transformation. Friendly aliases for every documented transformation code, short codes and `raw` accepted as keys, chains joined with `:`, `/` in values rendered as `@@`, path position by default with query position per request or via config, and extra query parameters. An unknown key throws `InvalidTransformation`; a request with no source, or two, throws `InvalidUrlRequest`.
- Signed URLs: `UrlRequest` takes `signed` and an optional `expiresIn` (seconds). Signing follows the current ImageKit docs: the path is percent-encoded, the trailing-slashed endpoint is stripped, the expiry (or `9999999999`) is appended, and the HMAC-SHA1 with `private_key` goes on as `ik-s`, preceded by `ik-t` when an expiry was given. "Now" comes from the injectable `Time\Clock`. Signing a `src`, an `expiresIn` without `signed`, or an `expiresIn` of zero or less throws `InvalidUrlRequest`.
- Tooling: Pest, Pint, PHPStan (Larastan, level 7), `composer ci` with a 100% coverage gate, and a GitHub Actions matrix over PHP 8.3–8.5 × Laravel 12–13.
