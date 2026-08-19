# Architecture

Agent-oriented component map for `netresearch/contexts-geolocation`. Facts here are verified against the source files listed; when in doubt, the source wins.

## System Overview

The extension adds geolocation-based context types to the base [netresearch/contexts](https://github.com/netresearch/t3x-contexts) TYPO3 extension. A context type decides per frontend request whether it "matches" (e.g. visitor is in Germany); the base extension uses that to toggle content visibility. Geolocation data comes from a MaxMind GeoIP2 database read through an adapter abstraction.

## Components

| Component | Path | Responsibility |
|-----------|------|----------------|
| Adapter interface | `Classes/Adapter/GeoIpAdapterInterface.php` | Contract: `lookup(string): ?GeoLocation`, `isAvailable(): bool` |
| MaxMind adapter | `Classes/Adapter/MaxMindGeoIp2Adapter.php` | Lazily opens `GeoIp2\Database\Reader` on the configured `.mmdb`, maps records to the DTO, returns `null` on unknown address |
| DTO | `Classes/Dto/GeoLocation.php` | `final readonly` value object (country/continent/coordinates/city fields, `hasCoordinates()`) |
| Service | `Classes/Service/GeoLocationService.php` | Client IP detection (PSR-7, optional proxy-header trust), private-IP filtering, adapter delegation |
| Context base class | `Classes/Context/Type/AbstractGeolocationContext.php` | Shared plumbing for context types: service lookup, request/IP access, list parsing; extends `Netresearch\Contexts\Context\AbstractContext` |
| Context types | `Classes/Context/Type/{Country,Continent,Distance}Context.php` | `match()` implementations per criterion; session-cached results |
| Exception | `Classes/Exception/GeoIpException.php` | Adapter/database failure signaling |
| DI wiring | `Configuration/Services.yaml` | Binds interface → MaxMind adapter (`%env(GEOIP_DATABASE_PATH)%`), configures service proxy trust (`%env(bool:GEOIP_TRUST_PROXY_HEADERS)%`) |
| Registration | `Configuration/TCA/Overrides/tx_contexts_contexts.php` | Registers the three context types + FlexForms via `Configuration::registerContextType()` |
| FlexForms | `Configuration/FlexForms/{Country,Continent,Distance}.xml` | Per-type editor configuration fields |

## Dependency Rules

Enforced by phpat in `Tests/Architecture/LayerTest.php` (config `Build/phpat.neon`, runs inside the unit test suite):

1. Classes in `Context\Type` must extend `Netresearch\Contexts\Context\AbstractContext`.
2. Classes in `Dto` must be `readonly`.
3. Classes in `Exception` must be `final`.
4. Classes in `Adapter` (except the interface itself) must implement `GeoIpAdapterInterface`.

## Data Flow

1. The base contexts extension instantiates a registered context type for a matching `tx_contexts_contexts` row and calls `match()`.
2. `match()` first consults the session cache (`getMatchFromSession()`); a hit short-circuits the lookup.
3. On a miss, the context reads its FlexForm config (`getConfValue('field_…')`), resolves the client IP (`AbstractGeolocationContext::getClientIpAddress()`), and discards private/reserved IPs.
4. `GeoLocationService` (fetched from the DI container, or injected in tests) delegates to the adapter; `MaxMindGeoIp2Adapter` reads the `.mmdb` database and returns a `GeoLocation` DTO or `null`.
5. The boolean result is inverted if configured, stored in the session (`storeInSession()`), and returned.

## Key Decisions

- **Adapter abstraction**: GeoIP providers are swappable behind `GeoIpAdapterInterface`; only the MaxMind implementation ships. Rationale in `Classes/AGENTS.md` (House Rules).
- **Environment-based configuration**: `GEOIP_DATABASE_PATH` and `GEOIP_TRUST_PROXY_HEADERS` are wired in `Configuration/Services.yaml`; there is no `ext_conf_template.txt`. Documented in `Documentation/Configuration/Index.rst`.
- **Public service**: `GeoLocationService` is `public: true` because context types are constructed by the base extension, not the container (comment in `Configuration/Services.yaml`).
- **CI layout**: extension-specific test matrix in `.github/workflows/ci.yml`, drift-enforced shared security gate in `.github/workflows/checks.yml` (rationale in the comments of both files).
