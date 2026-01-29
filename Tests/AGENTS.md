<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-01-28 -->

# AGENTS.md — Tests/

Test suite for the Contexts Geolocation extension.

## Overview

```
Tests/
├── Unit/           # Fast, isolated unit tests
└── Architecture/   # PHPat architecture tests
```

## Build & Tests

```bash
# Run all tests
composer test:unit

# Coverage (requires PCOV or Xdebug)
composer test:coverage

# Mutation testing
composer test:mutation
```

## Code Style & Conventions

### Test Class Naming

```php
Tests\Unit\Context\Type\GeolocationContextTest
Tests\Architecture\LayerTest
```

### Assertions

```php
self::assertTrue($result);
self::assertSame('DE', $country);
self::assertInstanceOf(GeolocationContext::class, $object);
```

## PR/Commit Checklist

- [ ] New functionality has corresponding tests
- [ ] All tests pass: `composer test:unit`
- [ ] Architecture tests pass with PHPat

## House Rules

- Unit tests must not require database or TYPO3 bootstrap
- Mock GeoIP2 responses for consistent testing
- Coverage target: maintain or improve current coverage
