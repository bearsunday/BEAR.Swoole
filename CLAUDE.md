# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

BEAR.Swoole provides Swoole HTTP server integration for BEAR.Sunday applications. It enables high-performance async HTTP serving by replacing Apache/nginx with Swoole's event-driven server.

**Requirements:** PHP 8.2+, Swoole extension

## Development Commands

```bash
# Run tests (requires Swoole extension)
composer test

# Run single test
./vendor/bin/phpunit --filter testMethodName

# Full quality check (cs + test + static analysis)
composer tests

# Fix coding standards
composer cs-fix

# Static analysis
composer sa

# Code coverage
composer pcov
```

## Testing Requirements

Tests require a running Swoole server. The test bootstrap (`tests/bootstrap.php`) starts a Swoole server on port 8088 using `tests/bin/swoole.php`. Tests make HTTP requests against this server using Guzzle.

## Architecture

### Request Flow

1. `bootstrap.php` - Entry point that creates and configures the Swoole HTTP server
2. `App` - Main application container holding router, responder, resource, and error handler
3. `SwooleModule` - Ray.Di module that binds Swoole-specific implementations
4. `SuperGlobals` - Populates `$_GET`, `$_POST`, `$_COOKIE`, `$_SERVER` from Swoole request
5. `Responder` - Transfers ResourceObject responses to Swoole Response

### Key Components

- `HttpCache` / `HttpCacheInterface` - ETag-based HTTP caching using BEAR.QueryRepository storage
- `SwooleRequestProvider` - Provides PSR-7 ServerRequest from Swoole request globals
- `SwooleServerRequest` - Builds PSR-7 ServerRequest from Swoole's `$_SERVER` equivalent

### Module Bindings

`SwooleModule` replaces standard BEAR.Sunday bindings:
- `TransferInterface` → `Responder` (Swoole response adapter)
- `RequestProviderInterface` → `SwooleRequestProvider` (PSR-7 from Swoole)
- `HttpCacheInterface` → `HttpCache` (304 Not Modified support)
- Cache pools use `CacheProvider` for in-memory caching suitable for long-running processes

## Test Application

`tests/Fake/` contains a minimal BEAR.Skeleton application used for integration tests. Resources include:
- `Page/Index` - Basic greeting response
- `Page/Cache` - Tests ETag/304 caching
- `Page/WebContext` - Tests web context parameter injection
- `Page/Psr7` - Tests PSR-7 ServerRequest injection
