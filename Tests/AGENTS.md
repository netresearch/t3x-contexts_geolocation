<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-08-19 -->

# AGENTS.md — Tests/

Test suite for the Contexts Geolocation extension.

## Overview

```
Tests/
├── Unit/           # Fast, isolated unit tests (Adapter/, Context/Type/, Dto/, Service/)
├── Functional/     # typo3/testing-framework functional tests (Context/Type/, Fixtures/)
└── Architecture/   # PHPat architecture tests (LayerTest.php)
```

## Setup

```bash
composer install    # installs PHPUnit, phpat, testing-framework into .Build/
```

Functional tests need a database; run them via `composer ci:test:php:functional`
(configured in `Build/phpunit/FunctionalTests.xml`) or containerized via
`Build/Scripts/runTests.sh`.

## Build & Tests

```bash
composer ci:test:php:unit        # Unit + architecture tests
composer ci:test:php:functional  # Functional tests (needs DB)
composer test:coverage           # Coverage (requires Xdebug)
composer test:mutation           # Mutation testing (Infection)
```

## Code Style & Conventions

### Test Class Naming

Mirror the class under test, `*Test` suffix:

```php
Netresearch\ContextsGeolocation\Tests\Unit\Context\Type\CountryContextTest
Netresearch\ContextsGeolocation\Tests\Unit\Service\GeoLocationServiceTest
Netresearch\ContextsGeolocation\Tests\Architecture\LayerTest
```

### Assertions

```php
self::assertTrue($result);
self::assertSame('DE', $country);
self::assertInstanceOf(GeoLocation::class, $object);
```

## Security

- Never commit real MaxMind `.mmdb` databases or license keys; mock `GeoIpAdapterInterface` instead
- Never use real visitor IPs in tests or fixtures; public resolver/documentation addresses (e.g. `8.8.8.8`, `203.0.113.50`) are fine

## PR/Commit Checklist

- [ ] New functionality has corresponding tests
- [ ] All tests pass: `composer ci:test:php:unit`
- [ ] Architecture tests pass with PHPat

## Good vs Bad Examples

```php
// Good: mock the adapter, wrap it in a real service, inject via constructor
$adapter = $this->createMock(GeoIpAdapterInterface::class);
$adapter->method('lookup')->with('8.8.8.8')->willReturn(new GeoLocation(countryCode: 'DE'));
$service = new GeoLocationService($adapter);
$context = $this->createTestableCountryContext('DE, US, FR', $service);

// Bad: hit the real MaxMind database (filesystem coupling, unstable results)
$context = new CountryContext($row); // resolves adapter with real GEOIP_DATABASE_PATH
```

## House Rules

- Unit tests must not require database or TYPO3 bootstrap
- Mock GeoIP2 responses for consistent testing
- Coverage target: maintain or improve current coverage

## When Stuck

- PHPUnit configs: `Build/phpunit/UnitTests.xml`, `Build/phpunit/FunctionalTests.xml`
- phpat rules: `Tests/Architecture/LayerTest.php`, config `Build/phpat.neon`
- TYPO3 testing docs: https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/Testing/Index.html
