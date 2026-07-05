# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

## [0.7.1] - 2026-07-06

### Fixed
- Expose Swoole's raw request body via `HTTP_RAW_POST_DATA` so JSON/form PUT/PATCH/POST bodies are no longer lost.
- Move router matching inside the request handler's try/catch so a malformed request body returns 400 instead of killing the worker.
- Widen the request handler's catch from `Exception` to `Throwable` so a scalar JSON body or an unknown method (via `_method` / `X-HTTP-Method-Override`) returns 500 instead of killing the worker.
- Treat RFC 9110 ETags (e.g. `W/"abc"`) that the PSR-6 etag pool cannot express as a cache miss instead of a 500.
- Drop a client-supplied `Raw-Post-Data` header before populating `HTTP_RAW_POST_DATA` from the real body, preventing header-based body injection.

## [0.7.0] - 2026-02-03

### Added
- `SwooleUriProvider` and `SwooleUploadfilesProvider`.
- Direct bindings for `ServerRequestInterface`, `UriInterface`, and `UploadFiles`.

### Changed
- Dependency `ray/psr7-module` updated to `^1.4`.
- `SwooleRequestProvider` now manages coroutine-safe request scope via autonomous discovery.
- Added `SwooleServerRequestConverter` for PSR-17 compliant request conversion.
- `bootstrap.php` refactored to seed the raw Swoole request into the coroutine context.
- `SwooleRequestProxy` refactored to lazy coroutine-context proxy.

### Deprecated
- Dependency on `Ray\HttpMessage\RequestProviderInterface` is now deprecated.

### Removed
- `SuperGlobals` class (non-coroutine-safe).
- `SwooleRequestContext` (redundant).
- `SwooleServerRequest` (legacy; replaced by `SwooleServerRequestConverter`).
