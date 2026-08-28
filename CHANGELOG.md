# Changelog

All notable changes to this package are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the package follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- Package scaffold: service provider, `config/imagekit-client.php` with env-var defaults for `public_key`, `private_key`, `url_endpoint`, `transformation_position`, `http.timeout` and `http.retries`, the `ImageKitClient` facade, and the `Client` contract exposing `files()` and `urls()`.
- `Configuration` value object, built on first resolve of the Client. A missing credential, an unknown transformation position, or a non-integer `http.*` value throws `InvalidConfiguration`.
- `ImageKitClientException` as the base of every exception this package throws.
- `urls()->build(UrlRequest)`: builds a delivery URL from a `path` (or an absolute `src`) and a Transformation. Friendly aliases for every documented transformation code, short codes and `raw` accepted as keys, chains joined with `:`, `/` in values rendered as `@@`, path position by default with query position per request or via config, and extra query parameters. An unknown key throws `InvalidTransformation`; a request with no source, or two, throws `InvalidUrlRequest`.
- Tooling: Pest, Pint, PHPStan (Larastan, level 7), `composer ci` with a 100% coverage gate, and a GitHub Actions matrix over PHP 8.3–8.5 × Laravel 12–13.
