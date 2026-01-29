# Changelog

All notable changes to this project will be documented in this file.

## [2.0.0] - 2026-01-28

### Added
- TYPO3 v12 LTS and v13 LTS support
- PHP 8.2, 8.3, 8.4, and 8.5 support
- Complete rewrite with modern architecture using MaxMind GeoIP2 library
- Environment-based configuration for GeoIP database path (GEOIP_DATABASE_PATH)
- Proxy header trust configuration (GEOIP_TRUST_PROXY_HEADERS)
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

## [1.x] - Legacy

See GitHub releases for version 1.x changelog.
