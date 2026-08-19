<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-08-19 -->

# AGENTS.md

**Project:** netresearch/contexts-geolocation — Geolocation context types for TYPO3
**Type:** TYPO3 CMS Extension (PHP 8.2+, TYPO3 12.4/13.4; version: see `ext_emconf.php`)

## Precedence

The **closest AGENTS.md** to changed files wins. This root file holds global defaults only.

## Global Rules

- Keep PRs small (~300 net LOC)
- Conventional Commits: `type(scope): subject`
- Ask before: heavy dependencies, architecture changes, new context types
- Never commit secrets, credentials, or PII
- Architecture overview: `docs/ARCHITECTURE.md`; execution plans: `docs/exec-plans/`

## Commands

```bash
# Code quality (run before committing):
composer ci:test:php:cgl      # PHP-CS-Fixer check (dry-run)
composer ci:test:php:phpstan  # PHPStan (Build/phpstan.neon)
composer ci:cgl               # Fix code style

# Testing:
composer ci:test:php:unit        # PHPUnit unit tests
composer ci:test:php:functional  # PHPUnit functional tests (needs DB)
composer test:coverage           # HTML coverage report (needs Xdebug)
composer test:mutation           # Infection mutation testing

# Makefile mirrors: make cgl / cgl-fix / phpstan / test / test-unit / test-functional
# Containerized matrix runs: Build/Scripts/runTests.sh [options] [suite]
```

## Development Environment

```bash
# DDEV setup (recommended)
ddev start
ddev install-all          # Install TYPO3 v12 + v13 (or install-v12 / install-v13)

# Access
https://v12.contexts-geolocation.ddev.site/typo3/    # TYPO3 v12 backend
https://v13.contexts-geolocation.ddev.site/typo3/    # TYPO3 v13 backend

# Credentials: admin / Password:joh316!
```

## CI Workflows

| Workflow | Trigger | Purpose |
|----------|---------|---------|
| `ci.yml` | push/PR/merge_group, weekly | Test matrix (PHP 8.2–8.5 × TYPO3 ^12.4/^13.4, MySQL functional tests) via reusable `netresearch/typo3-ci-workflows` |
| `checks.yml` | push/PR/merge_group, weekly | Security/quality gate: composer audit, gitleaks, zizmor, CodeQL, fuzz, license check, Scorecard, dependency review, PR quality — gated by `All security checks` |
| `check-template-drift.yml` | PR, weekly | Keeps `checks.yml` byte-identical to the org template |
| `harness-verify.yml` | push/PR | Agent-harness consistency (AGENTS.md budget, refs, docs/) |
| `release.yml` | tag | Release + TER publishing pipeline |
| `republish.yml` | manual | Re-run TER publishing for an existing tag |
| `labeler.yml` / `community.yml` / `auto-merge-deps.yml` | PR/misc | Labels, community hygiene, dependency auto-merge |

## Project Structure

```
Classes/                       # PHP source (see Classes/AGENTS.md)
├── Adapter/                   # GeoIpAdapterInterface, MaxMindGeoIp2Adapter
├── Context/Type/              # AbstractGeolocationContext, Country/Continent/DistanceContext
├── Service/                   # GeoLocationService
├── Exception/                 # GeoIpException
└── Dto/                       # GeoLocation value object
Tests/                         # Unit/, Functional/, Architecture/ (phpat)
Configuration/                 # TCA/Overrides/, FlexForms/, Services.yaml
Resources/                     # Language files, assets
Documentation/                 # RST docs for docs.typo3.org
Build/                         # phpunit/phpstan/phpat configs, Scripts/
docs/                          # ARCHITECTURE.md, exec-plans/
```

## Index of scoped AGENTS.md

| Path | Purpose |
|------|---------|
| `./Classes/AGENTS.md` | PHP backend code, adapters, context types |
| `./Configuration/AGENTS.md` | TCA registration, FlexForms, DI services |
| `./Documentation/AGENTS.md` | RST documentation conventions |
| `./Tests/AGENTS.md` | Unit/functional/architecture test suite |

## Dependencies

**Required:** `netresearch/contexts` ^3.1.1 || ^4.0 (base contexts extension), `geoip2/geoip2` ^3.0 (MaxMind GeoIP2 PHP library)

## Key Concepts

| Context Type | TCA key | FlexForm fields |
|-------------|---------|-----------------|
| `CountryContext` | `geolocation_country` | `field_countries` (ISO 3166-1 alpha-2, comma-separated) |
| `ContinentContext` | `geolocation_continent` | `field_continents` (AF, AN, AS, EU, NA, OC, SA) |
| `DistanceContext` | `geolocation_distance` | `field_latitude`, `field_longitude`, `field_radius` (km) |

Context types are registered in `Configuration/TCA/Overrides/tx_contexts_contexts.php` via `Configuration::registerContextType()`. GeoIP lookups go through `GeoLocationService` → `GeoIpAdapterInterface`; results are cached in the session. Private/reserved IPs never match.

## Configuration

Runtime configuration is environment-based (wired in `Configuration/Services.yaml`):

```
GEOIP_DATABASE_PATH        # Path to MaxMind .mmdb database (GeoLite2 or GeoIP2)
GEOIP_TRUST_PROXY_HEADERS  # bool: trust X-Forwarded-For / X-Real-IP
```

## When Instructions Conflict

Nearest AGENTS.md wins. User prompts override files.

## Resources

- [MaxMind GeoIP2 PHP](https://github.com/maxmind/GeoIP2-php)
- [MaxMind GeoLite2 Free Databases](https://dev.maxmind.com/geoip/geolite2-free-geolocation-data)
- [TYPO3 Coding Guidelines](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/CodingGuidelines/Index.html)
- [Base Extension](https://github.com/netresearch/t3x-contexts)
- [GitHub Issues](https://github.com/netresearch/t3x-contexts_geolocation/issues)

## Commit Signing

Signed commits are required: `git commit -S --signoff`. The `require-signed-commits` ruleset on the default branch rejects unsigned commits at merge time, and the DCO check additionally requires the `Signed-off-by` trailer. Quickest setup is SSH signing — register your SSH key as a *signing key* on your GitHub account, then `git config --global gpg.format ssh && git config --global user.signingkey ~/.ssh/<key>.pub`.
