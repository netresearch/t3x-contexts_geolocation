<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-08-19 -->

# AGENTS.md - Classes/

Backend PHP code for the Contexts Geolocation extension.

## Overview

This directory contains the core PHP implementation:
- **Adapter/**: `GeoIpAdapterInterface` + `MaxMindGeoIp2Adapter` (MaxMind GeoIP2 reader)
- **Context/Type/**: `AbstractGeolocationContext` base class + `CountryContext`, `ContinentContext`, `DistanceContext`
- **Service/**: `GeoLocationService` (IP detection, proxy headers, adapter lookups)
- **Dto/**: `GeoLocation` immutable value object
- **Exception/**: `GeoIpException`

Read the actual source files before editing — do not rely on snippets in this document staying byte-identical to the code.

## Setup & Environment

```bash
composer install
ddev start && ddev install-v13
```

## Build & Tests

```bash
composer ci:test:php:cgl      # PHP-CS-Fixer
composer ci:test:php:phpstan  # PHPStan (Build/phpstan.neon)
composer ci:test:php:unit     # Unit tests for this code
```

## Code Style & Conventions

### PSR-12 + TYPO3 CGL

- Strict types: `declare(strict_types=1);`
- SPDX + package license header at the top of every file
- Final classes by default; context types stay non-final and extend `AbstractGeolocationContext`
- Constructor property promotion where applicable; `final readonly` for services and DTOs
- Return types on all methods

### Namespace Pattern

```php
namespace Netresearch\ContextsGeolocation\Adapter;
namespace Netresearch\ContextsGeolocation\Context\Type;
namespace Netresearch\ContextsGeolocation\Service;
namespace Netresearch\ContextsGeolocation\Dto;
namespace Netresearch\ContextsGeolocation\Exception;
```

### Dependency Injection

Services are wired in `Configuration/Services.yaml`. The adapter binding and its
database path come from there:

```yaml
Netresearch\ContextsGeolocation\Adapter\GeoIpAdapterInterface:
  class: Netresearch\ContextsGeolocation\Adapter\MaxMindGeoIp2Adapter
  arguments:
    $databasePath: '%env(GEOIP_DATABASE_PATH)%'
```

`GeoLocationService` is declared `public: true` because context types are
instantiated by the base contexts extension (not the container) and fetch the
service from the container at runtime; `AbstractGeolocationContext::__construct()`
also accepts it as an optional argument for tests.

## Extension-Specific Patterns

### GeoIP Adapter Contract

`GeoIpAdapterInterface` (see `Adapter/GeoIpAdapterInterface.php`):

```php
public function lookup(string $ipAddress): ?GeoLocation;  // null if IP unresolvable
public function isAvailable(): bool;                      // database present & readable
```

`MaxMindGeoIp2Adapter` lazily opens a `GeoIp2\Database\Reader` on the configured
`.mmdb` path, maps the record into the `GeoLocation` DTO, and returns `null` on
`AddressNotFoundException`.

### GeoLocation DTO

`Dto/GeoLocation.php` is a `final readonly` value object with nullable public
properties (`countryCode`, `countryCode3`, `countryName`, `continentCode`,
`continentName`, `latitude`, `longitude`, `city`, `postalCode`, `region`,
`regionName`) and a `hasCoordinates(): bool` helper that rejects null and 0.0/0.0
coordinates.

### Context Types

All three context types extend `Context/Type/AbstractGeolocationContext`, which
extends `Netresearch\Contexts\Context\AbstractContext` and provides the shared
plumbing: `getGeoLocationService()`, `getRequest()`, `getClientIpAddress()`,
`isPrivateIp()`, `parseCommaSeparatedList()`.

The `match()` skeleton (see `CountryContext::match()` for the reference
implementation):

```php
public function match(array $arDependencies = []): bool
{
    [$bUseSession, $bMatch] = $this->getMatchFromSession();
    if ($bUseSession) {
        return $this->invert((bool) $bMatch);
    }
    // ... resolve config via $this->getConfValue('field_...'), look up IP ...
    return $this->storeInSession($this->invert($bMatch));
}
```

FlexForm fields per type: `field_countries` (Country), `field_continents`
(Continent), `field_latitude` / `field_longitude` / `field_radius` (Distance).
`DistanceContext` computes great-circle distance with the Haversine formula
(`calculateHaversineDistance()`, `EARTH_RADIUS_KM = 6371.0`).

### GeoLocation Service

`Service/GeoLocationService.php` (`final readonly`):

- `getLocationForRequest(): ?GeoLocation` — uses `$GLOBALS['TYPO3_REQUEST']`; returns `null` when no PSR-7 request is available
- `getLocationForIp(string $ip): ?GeoLocation`
- `getClientIpAddress(ServerRequestInterface $request): ?string` — checks configured proxy headers (`X-Forwarded-For`, `X-Real-IP`) only when `$trustProxyHeaders` is true, validates every candidate IP, falls back to `REMOTE_ADDR`
- `isPrivateIp(string $ip): bool` — `filter_var()` with `FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE`
- `isAvailable(): bool` — delegates to the adapter

Private/reserved/invalid IPs always yield `null` (no lookup).

## Security & Safety

- Validate IP addresses before lookup
- Handle `AddressNotFoundException` gracefully (return `null`)
- Never expose raw GeoIP database errors to users
- Cache results in session to minimize lookups
- Use TYPO3's PSR-7 request instead of `$_SERVER` directly

## Testing Patterns

- Unit tests live in `Tests/Unit/` mirroring this directory (`Adapter/`, `Context/Type/`, `Dto/`, `Service/`); they must not require a database or TYPO3 bootstrap
- Functional tests live in `Tests/Functional/` on `typo3/testing-framework`
- Architecture rules are enforced by phpat in `Tests/Architecture/LayerTest.php`
- See `Tests/AGENTS.md` for commands and conventions

## PR/Commit Checklist

- [ ] `composer ci:test:php:cgl` passes
- [ ] `composer ci:test:php:phpstan` passes
- [ ] Unit tests added/updated for new functionality
- [ ] Strict types declared
- [ ] Return types on all methods
- [ ] GeoIP adapter implements interface
- [ ] IP validation handles both IPv4 and IPv6
- [ ] Private IPs handled correctly (return null)

## Good vs Bad Examples

### Adapter Pattern

```php
// Good: Dependency injection with interface
public function __construct(
    private readonly GeoIpAdapterInterface $adapter,
) {}

// Bad: Static singleton (legacy pattern)
$adapter = AbstractAdapter::getInstance($ip);
```

### IP Address Access

```php
// Good: PSR-7 request with configurable proxy trust
$ip = $service->getClientIpAddress($request);

// Bad: Direct $_SERVER access
$ip = $_SERVER['REMOTE_ADDR'];
```

### Error Handling

```php
// Good: Graceful handling, return null
try {
    return $this->mapRecord($reader->city($ip));
} catch (AddressNotFoundException) {
    return null;
}

// Bad: Let exceptions bubble up
return $reader->city($ip); // Throws on unknown IP!
```

### Coordinate Validation

```php
// Good: Check for null AND zero coordinates
if ($location === null || !$location->hasCoordinates()) {
    return $this->storeInSession($this->invert(false));
}

// Bad: Only check for false (legacy pattern)
if ($arPosition === false) {
    return $bUnknown;
}
```

## House Rules

- GeoIP adapter must be swappable via DI (interface-based)
- Session caching via `getMatchFromSession()` / `storeInSession()` is mandatory in every `match()`
- Distance calculations use the Haversine formula (great-circle distance)
- MaxMind database path is configured via the `GEOIP_DATABASE_PATH` environment variable
- Support both GeoLite2 (free) and GeoIP2 (commercial) databases
- Architecture boundaries are enforced by `Tests/Architecture/LayerTest.php` — see `docs/ARCHITECTURE.md`

## When Stuck

- MaxMind GeoIP2 PHP: https://github.com/maxmind/GeoIP2-php
- GeoLite2 databases: https://dev.maxmind.com/geoip/geolite2-free-geolocation-data
- TYPO3 Core API: https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/
- Extension issues: https://github.com/netresearch/t3x-contexts_geolocation/issues
