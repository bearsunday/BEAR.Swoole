# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added
- `SwooleUriProvider` and `SwooleUploadfilesProvider`.
- Direct bindings for `ServerRequestInterface`, `UriInterface`, and `UploadFiles`.

### Changed
- Dependency `ray/psr7-module` updated to `^1.4`.
- `SwooleRequestProvider` now manages coroutine-safe request scope via autonomous discovery.
- Added `SwooleServerRequestConverter` for PSR-17 compliant request conversion.
- `bootstrap.php` refactored to seed the raw Swoole request into the coroutine context.

### Deprecated
- Dependency on `Ray\HttpMessage\RequestProviderInterface` is now deprecated.

### Removed
- `SuperGlobals` class (non-coroutine-safe).
- `SwooleRequestContext` (redundant).
- `SwooleRequestProxy` (refactored to lazy coroutine-context proxy).
- `SwooleServerRequest` (legacy; replaced by `SwooleServerRequestConverter`).
