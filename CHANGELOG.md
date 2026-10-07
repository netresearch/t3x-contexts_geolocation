<!-- SPDX-License-Identifier: AGPL-3.0-or-later -->
<!-- SPDX-FileCopyrightText: Netresearch DTT GmbH -->
# Changelog

All notable changes to this project will be documented in this file.

## [2.0.0] - Unreleased

### Added
- TYPO3 v12 LTS and v13 LTS support
- PHP 8.2, 8.3, 8.4, and 8.5 support
- Complete rewrite with modern architecture using MaxMind GeoIP2 library
- Environment-based configuration for GeoIP database path (GEOIP_DATABASE_PATH)
- Client IP address from TYPO3's reverse proxy configuration (`$GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP']`)
- Session-based caching for efficient geolocation lookups
- Three context types:
  - **Continent Context**: Match visitors by continent (AF, AN, AS, EU, NA, OC, SA)
  - **Country Context**: Match visitors by ISO 3166-1 alpha-2 country codes
  - **Distance Context**: Match visitors within a radius from geographic coordinates
- Full type safety with strict PHP 8.2+ typing
- Comprehensive unit and functional test suite
- PHPStan level 10 compliance (strict static analysis)
- Mutation testing to ensure code quality
- CI/CD integration with GitHub Actions
- Full documentation in reStructuredText format

### Changed
- **Breaking**: Complete architectural rewrite from legacy PECL geoip extension to MaxMind GeoIP2
- **Breaking**: Minimum TYPO3 version now 12.4 LTS (dropped TYPO3 11 and earlier)
- **Breaking**: Minimum PHP version now 8.2 (dropped PHP 8.1 and earlier)
- **Breaking**: Configuration system moved to environment variables
- Geolocation detection now uses MaxMind GeoIP2 library
- All classes moved to Netresearch\ContextsGeolocation namespace
- FlexForms configuration updated for TYPO3 12/13
- TCA configuration modernized for current TYPO3 versions

### Removed
- Support for TYPO3 11 and earlier
- Support for PHP 8.1 and earlier
- Legacy PECL geoip extension integration
- Legacy PEAR Net_GeoIP integration
- Legacy extension settings in TYPO3 backend (now environment-based)

### Fixed
- Improved geolocation accuracy using modern MaxMind GeoIP2 data
- Session-based caching prevents repeated database lookups
- Proper error handling for missing or invalid GeoIP database

### Dependencies
- Updated to MaxMind GeoIP2 ^3.0
- Updated to TYPO3 12.4/13.4 LTS versions
- Updated all dev dependencies to latest versions supporting PHP 8.2+

## [1.0.5] - 2025-11-20

### Changed
- Extension metadata: the author company in `ext_emconf.php` is now
  "Netresearch DTT GmbH". No code change; updating from 1.0.4 needs no action.

## [1.0.4] - 2025-11-20

### Added
- Extension icon for the TYPO3 Extension Repository
  (`Resources/Public/Icons/Extension.svg`).
- GitHub Actions workflow that publishes tagged versions to the TYPO3 Extension
  Repository.
- Renovate configuration for dependency update pull requests.

### Changed
- `README.rst` replaced by `README.md` with status badges.
- `composer.json` declares the extension key
  (`extra.typo3/cms.extension-key`).
- `ext_emconf.php` version set to 1.0.4; releases 1.0.1 to 1.0.3 had left it
  at 1.0.0.

No code change; updating from 1.0.3 needs no action.

## [1.0.3] - 2023-11-09

### Changed
- `composer.json` declares the licence as AGPL-3.0-or-later, matching the
  `LICENSE` file added in 1.0.1 (it said GPL-2.0+ before).

### Fixed
- `composer.json` no longer replaces its own package name
  `netresearch/contexts_geolocation`.

## [1.0.2] - 2021-12-24

### Fixed
- `composer.json`: Unix line endings and the lower-case package name
  `mikey179/vfsstream`.

## [1.0.1] - 2021-12-24

### Added
- `LICENSE` file (GNU AGPL 3.0).

### Fixed
- Country and continent FlexForms set the `renderType` of their select
  fields.
- Class references to the PEAR `Net_GeoIP` library use the global namespace.

## [1.0.0] - 2017-01-10

### Changed
- Compatibility with TYPO3 6.2 to 8.x; TYPO3 4.5 to 6.1 are no longer
  supported.
- The client address is read through the base extension's
  `getRemoteAddress()` instead of `$_SERVER['REMOTE_ADDR']`.
- The backend map for the distance context uses plain JavaScript.
- `composer.json` added; stability set to stable.

## 0.x

Changes before 1.0.0 are listed in the `ChangeLog` file.
