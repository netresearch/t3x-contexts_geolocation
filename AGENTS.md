<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-01-30 -->

# AGENTS.md

**Project:** netresearch/contexts_geolocation — Geolocation context types for TYPO3
**Type:** TYPO3 CMS Extension (PHP 8.2+, TYPO3 12.4/13.4)

## Precedence

The **closest AGENTS.md** to changed files wins. This root file holds global defaults only.

## Global Rules

- Keep PRs small (~300 net LOC)
- Conventional Commits: `type(scope): subject`
- Ask before: heavy dependencies, architecture changes, new context types
- Never commit secrets, credentials, or PII
- GrumPHP runs pre-commit checks automatically

## Pre-Commit Checks (GrumPHP)

```bash
# Automatic on commit (via GrumPHP):
composer lint          # PHP_CodeSniffer (PSR-12 + TYPO3 CGL)
composer analyze       # PHPStan level 10

# Manual testing:
composer test:unit        # PHPUnit unit tests
composer test:functional  # PHPUnit functional tests (needs DB)
composer test:coverage    # Coverage report (needs PCOV/Xdebug)
```

## Development Environment

```bash
# DDEV setup (recommended)
ddev start
ddev install-all          # Install TYPO3 v12, v13

# Access
https://v12.contexts-geolocation.ddev.site/typo3/    # TYPO3 v12 backend
https://v13.contexts-geolocation.ddev.site/typo3/    # TYPO3 v13 backend

# Credentials: admin / Password:joh316!
```

## CI Workflows

| Workflow | Trigger | Purpose |
|----------|---------|---------|
| `ci.yml` | push/PR | Full test suite (unit, functional, lint, phpstan) |
| `phpstan.yml` | push/PR | Static analysis |
| `security.yml` | schedule | Dependency vulnerability scan |
| `scorecard.yml` | push to main, weekly | OpenSSF Scorecard security analysis |
| `slsa-provenance.yml` | release | SLSA Level 3 provenance attestation |
| `publish-to-ter.yml` | tag | Publish to TYPO3 Extension Repository |

## Project Structure (Target)

```
Classes/                       # PHP source code
├── Adapter/                   # GeoIP adapter implementations
│   ├── GeoIpAdapterInterface.php
│   └── MaxMindGeoIp2Adapter.php
├── Context/Type/              # Context type implementations
│   ├── CountryContext.php
│   ├── ContinentContext.php
│   └── DistanceContext.php
├── Service/                   # Business logic services
│   └── GeoLocationService.php
├── Exception/                 # Custom exceptions
│   └── GeoIpException.php
└── Dto/                       # Value objects for geolocation data
    └── GeoLocation.php
Tests/                         # Test suite
├── Unit/                      # Unit tests
│   ├── Adapter/
│   └── Context/Type/
└── Functional/                # Functional tests
Configuration/                 # TYPO3 configuration
├── TCA/Overrides/
├── FlexForms/
├── Services.yaml
└── SiteSet/                   # v13 site sets
Resources/                     # Language files, assets
Documentation/                 # RST documentation
```

## Index of Scoped AGENTS.md

| Path | Purpose |
|------|---------|
| `Classes/AGENTS.md` | PHP backend code, adapters, context types |

## Dependencies

**Required:**
- `netresearch/contexts` ^4.0 - Base contexts extension
- `geoip2/geoip2` ^3.0 - MaxMind GeoIP2 PHP library

**Suggested:**
- `sjbr/static-info-tables` - Country/continent metadata

## Key Concepts

### MaxMind GeoIP2 Integration

The extension uses MaxMind GeoIP2 library for IP geolocation:

```php
// GeoLocation databases (configure path in extension settings)
// - GeoLite2-Country.mmdb (free, country-level)
// - GeoLite2-City.mmdb (free, city-level with coordinates)
// - GeoIP2-Country.mmdb (commercial, higher accuracy)
// - GeoIP2-City.mmdb (commercial, higher accuracy)
```

### Context Types

| Context Type | Purpose | Configuration Fields |
|-------------|---------|---------------------|
| `CountryContext` | Match by country code | Countries (ISO 3166-1), Unknown handling |
| `ContinentContext` | Match by continent | Continents (AF, AS, EU, NA, OC, SA, AN), Unknown handling |
| `DistanceContext` | Match by distance from point | Latitude, Longitude, Radius (km), Unknown handling |

### IP Address Handling

```php
// IPv4 and IPv6 support
// Trust proxy headers (X-Forwarded-For, X-Real-IP) when configured
// Fallback to REMOTE_ADDR
// Special handling for private/local IPs (return unknown)
```

## Configuration

### Extension Configuration (ext_conf_template.txt)

```
# MaxMind GeoIP2 database path
geoipDatabasePath = EXT:contexts_geolocation/Resources/Private/GeoIP/GeoLite2-City.mmdb

# Trust proxy headers for IP detection
trustProxyHeaders = 0

# Proxy header priority (comma-separated)
proxyHeaders = X-Forwarded-For,X-Real-IP
```

## When Instructions Conflict

Nearest AGENTS.md wins. User prompts override files.

## Resources

- [MaxMind GeoIP2 PHP](https://github.com/maxmind/GeoIP2-php)
- [MaxMind GeoLite2 Free Databases](https://dev.maxmind.com/geoip/geolite2-free-geolocation-data)
- [TYPO3 Coding Guidelines](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/CodingGuidelines/Index.html)
- [Base Extension](https://github.com/netresearch/t3x-contexts)
- [GitHub Issues](https://github.com/netresearch/t3x-contexts_geolocation/issues)
