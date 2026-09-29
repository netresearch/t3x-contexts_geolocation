<!-- SPDX-License-Identifier: AGPL-3.0-or-later -->
<!-- SPDX-FileCopyrightText: Netresearch DTT GmbH -->
# Security assurance

This document states what users can expect from `netresearch/contexts-geolocation` in terms of security, which threats the design considers, and where the code counters them. Every claim names the file that implements it. Components and data flow are described in [ARCHITECTURE.md](ARCHITECTURE.md). Vulnerabilities are reported as described in [SECURITY.md](../SECURITY.md).

## What the extension does, security-wise

The extension adds three context types (country, continent, distance) to the base extension [netresearch/contexts](https://github.com/netresearch/t3x-contexts). For each frontend request that evaluates one of them, it resolves the visitor's IP address to a location in a local MaxMind database and returns whether the location matches the configured values.

- **Data it processes**: the visitor's IP address. `GeoLocationService::getClientIpAddress()` (`Classes/Service/GeoLocationService.php`) reads `REMOTE_ADDR` from the PSR-7 request. Only when `GEOIP_TRUST_PROXY_HEADERS` is `true` (default `false`, `Configuration/Services.yaml`) does it read `X-Forwarded-For` and then `X-Real-IP` first, taking the first address of the header. The database returns country, continent, coordinates, city, postal code and subdivision (`Classes/Adapter/MaxMindGeoIp2Adapter.php`, `Classes/Dto/GeoLocation.php`); the context types use the country code, the continent code or the coordinates.
- **Where the location data comes from**: a MaxMind GeoLite2 or GeoIP2 City database file (`.mmdb`) on the server, at the path in the environment variable `GEOIP_DATABASE_PATH` (`Configuration/Services.yaml`). The operator downloads and updates it, for example with `geoipupdate` ([README.md](../README.md), `Documentation/Configuration/GeoIP.rst`). The extension opens it read-only through `GeoIp2\Database\Reader` and makes no network requests. The MaxMind account and licence key are used by the operator's download tool; the extension never reads them.
- **What it stores**: this extension stores neither the IP address nor the location. The only state that outlives the request is the boolean match result, which the base extension's `AbstractContext::storeInSession()` writes to the TYPO3 frontend user session under the key `contexts-<uid>-<tstamp>` when the context record has "use session" enabled. The extension has no database tables (no `ext_tables.sql`) and writes no log entries.
- **What it outputs**: nothing. It returns a boolean to the base extension, which shows or hides content.

## Security expectations

Users can expect:

- The IP address is validated with `filter_var(..., FILTER_VALIDATE_IP)` before it is used; invalid values from `REMOTE_ADDR` or from the proxy headers are ignored (`GeoLocationService::isValidIpAddress()`).
- Private, loopback, link-local and reserved addresses are never looked up and never match (`GeoLocationService::isPrivateIp()`, `FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE`).
- Proxy headers are ignored unless the operator enables `GEOIP_TRUST_PROXY_HEADERS`.
- A context whose configuration is empty or out of range does not match: `DistanceContext::isValidConfiguration()` requires numeric latitude in [-90, 90], longitude in [-180, 180] and a radius of 0 or more.
- The database path cannot be set from a request or by a backend editor; it comes only from the environment.

Users cannot expect:

- **Access control.** IP geolocation is a targeting heuristic. Visitors using VPNs, proxies or mobile networks are located elsewhere than they are ([README.md](../README.md), "Accuracy Considerations"). Do not use these contexts to protect content that a visitor must not see.
- **Correct results behind a reverse proxy without configuration.** With `GEOIP_TRUST_PROXY_HEADERS=false`, `REMOTE_ADDR` is the proxy's address, usually private, so no geolocation context matches. With `true`, the extension does not check which host sent the header; enable it only when the reverse proxy in front of TYPO3 sets `X-Forwarded-For` or `X-Real-IP` itself and does not pass on values sent by the client.
- **A match when the location is unknown.** Every failure (no request, no service, private IP, address not in the database, missing coordinates) evaluates to "no match" before the context's invert option is applied. An inverted context therefore matches in these cases.
- **Graceful degradation without a database.** If the file at `GEOIP_DATABASE_PATH` is missing, unreadable or not a MaxMind database, `MaxMindGeoIp2Adapter::lookup()` throws `GeoIpException`, and the context types do not catch it. The frontend request then fails with TYPO3's error handling, so the operator has to keep the file in place. If `GEOIP_DATABASE_PATH` is not set at all, the service cannot be created and `AbstractGeolocationContext::getGeoLocationService()` returns `null`, so the contexts do not match.
- **Current location data.** Accuracy depends on the age of the database file, which the extension does not check.
- **Data protection compliance.** Processing IP addresses is processing personal data; the operator decides on the lawful basis and informs visitors (`Documentation/Configuration/GeoIP.rst`, "Privacy and Data Protection").

## Threat model and trust boundaries

Actors:

- **Visitor**: anonymous, untrusted. Controls the HTTP request, including the `X-Forwarded-For` and `X-Real-IP` headers, and chooses the network, and thus the address, it connects from.
- **Backend editor**: authenticated TYPO3 backend user who creates context records and fills the FlexForm fields (countries, continents, latitude, longitude, radius). Trusted to configure targeting, not to run code.
- **Operator**: installs the extension, sets `GEOIP_DATABASE_PATH` and `GEOIP_TRUST_PROXY_HEADERS`, provides and updates the database file. Fully trusted.
- **MaxMind**: supplier of the database. Trusted for the content of the file the operator installs.

Trust boundaries:

1. **HTTP request to extension.** The client address and headers are untrusted. They are validated as IP addresses and filtered for private ranges before a lookup (`Classes/Service/GeoLocationService.php`). Headers are read only when the operator has declared the proxy trusted.
2. **Backend configuration to extension.** FlexForm values are strings from the database. Country and continent lists are split, trimmed, upper-cased and compared with `in_array(..., true)` (`AbstractGeolocationContext::parseCommaSeparatedList()`, `CountryContext`, `ContinentContext`); distance values are range-checked before use (`DistanceContext::isValidConfiguration()`). None of them reaches a file path, a query or output.
3. **Database file to extension.** The file is parsed by the MaxMind reader library (`geoip2/geoip2`, `maxmind-db/reader`). A malformed file raises `InvalidDatabaseException`, which the adapter converts into `GeoIpException` (`MaxMindGeoIp2Adapter::getReader()`, `lookup()`).
4. **Extension to base extension.** The match result goes to `netresearch/contexts`, which caches it in the frontend session and applies it to page and content visibility.

Assets: the visitor's IP address (personal data), the correctness of content targeting, and the availability of frontend pages that evaluate geolocation contexts.

Threats considered and how they are handled:

| Threat | Handling |
| --- | --- |
| Visitor forges a location to see or avoid targeted content | Not prevented; geolocation is not access control (see expectations). Proxy headers are ignored by default. |
| Crafted address strings reach the database reader | `FILTER_VALIDATE_IP` on every candidate; invalid values are dropped (`GeoLocationService`). |
| Internal addresses are looked up or matched | `isPrivateIp()` excludes private and reserved ranges before every lookup. |
| Editor input causes code or file access | FlexForm values are only compared or converted to floats; no path, query or output uses them. |
| IP addresses leak through storage or logs | The extension persists no IP or location and has no logging calls. |
| Database missing or corrupt | `GeoIpException` with the reader's exception attached (`MaxMindGeoIp2Adapter`); covered by `Tests/Unit/Adapter/MaxMindGeoIp2AdapterTest.php`. |

## Secure design principles applied

- **Secure defaults**: proxy headers are distrusted unless `GEOIP_TRUST_PROXY_HEADERS` is set (`GeoLocationService` constructor default `false`, `Services.yaml`).
- **Fail closed**: any error in resolving a location yields "no match" before inversion (`CountryContext::match()`, `ContinentContext::match()`, `DistanceContext::match()`).
- **Minimal data**: the location is kept in memory for the request only; the session holds a boolean.
- **Least privilege**: the database is opened read-only; the extension needs no write access, no network access and no database tables.
- **Configuration outside the web request**: the database path and the proxy trust come from the environment, not from TYPO3 settings an editor can change.
- **Small attack surface**: no frontend plugin, no controller, no output, no console command.
- **Enforced layering**: `Tests/Architecture/LayerTest.php` (phpat) requires DTOs to be readonly, exceptions final and adapters to implement `GeoIpAdapterInterface`.

## Countering common weaknesses

| Weakness | Where it is countered |
| --- | --- |
| CWE-20 Improper input validation | IP validation and private-range filtering in `GeoLocationService`; range checks in `DistanceContext::isValidConfiguration()`. |
| CWE-79 Cross-site scripting | The extension renders no output. |
| CWE-89 SQL injection | The extension runs no database queries; the base extension reads the context records. |
| CWE-22 Path traversal | The only file opened is the one at `GEOIP_DATABASE_PATH`, set by the operator. |
| CWE-918 Server-side request forgery | No outbound network requests; lookups read the local file. |
| CWE-532 Sensitive information in log files | No logging calls in `Classes/`. |
| CWE-209 Error messages containing sensitive information | `GeoIpException` messages name the database path; they reach visitors only if TYPO3 is configured to display errors, which production configurations do not. |
| CWE-1104 Use of unmaintained third-party components | Renovate (`renovate.json`) proposes dependency updates; Composer Audit and Dependency Review run on every pull request (see [CONTRIBUTING.md](../CONTRIBUTING.md#governance-and-policies)). |

## Verification

- Unit tests cover the service (IP detection, proxy headers, private ranges), the adapter (success, unknown address, invalid database) and the three context types; functional tests run the context types inside TYPO3 (`Tests/`).
- PHPStan (`Build/phpstan.neon`), Opengrep, CodeQL, Composer Audit, Dependency Review and secret scanning run on every pull request; the list is in [CONTRIBUTING.md](../CONTRIBUTING.md#governance-and-policies).
