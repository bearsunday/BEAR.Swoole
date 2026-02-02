# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

BEAR.Swoole provides Swoole HTTP server integration for BEAR.Sunday applications. It enables high-performance async HTTP serving by replacing Apache/nginx with Swoole's event-driven server.

**Requirements:** PHP 8.2+, ext-swoole ^6.1

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

1. `bootstrap.php` - Entry point that creates Swoole HTTP server and handles request events
2. `SwooleRequestProvider::seed()` - Seeds coroutine context with raw Swoole request and `$_SERVER`-compatible array
3. `App` - Main application container holding router, responder, resource, and error handler
4. `Responder` - Transfers ResourceObject responses to Swoole Response

### Coroutine-Based Request Isolation

This library uses Swoole coroutine context instead of PHP superglobals for request isolation:

- `SwooleRequestProvider::seed()` stores raw Swoole request in coroutine context
- `SwooleRequestProxy` implements `ServerRequestInterface` as a lazy proxy that retrieves the actual PSR-7 request from coroutine context on demand
- `SwooleServerRequestConverter` converts Swoole request to PSR-7 `ServerRequestInterface` (conversion happens lazily only when PSR-7 is actually needed)
- Parent coroutine context is searched if current context lacks request data

### Module Bindings

`SwooleModule` replaces standard BEAR.Sunday bindings:
- `TransferInterface` → `Responder` (Swoole response adapter)
- `RequestProviderInterface` → `SwooleRequestProvider` (provides PSR-7 via coroutine context)
- `ServerRequestInterface` → `SwooleRequestProxy` (lazy proxy to actual PSR-7 request)
- `HttpCacheInterface` → `HttpCache` (304 Not Modified support)
- Cache pools use `CacheProvider` for in-memory caching suitable for long-running processes

## Test Application

`tests/Fake/` contains a minimal BEAR.Skeleton application used for integration tests.
