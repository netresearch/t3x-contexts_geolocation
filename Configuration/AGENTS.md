<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-08-19 -->

# AGENTS.md — Configuration/

TYPO3 configuration files for the Contexts Geolocation extension.

## Overview

```
Configuration/
├── TCA/Overrides/
│   └── tx_contexts_contexts.php   # Context type registration
├── FlexForms/
│   ├── Country.xml                # field_countries
│   ├── Continent.xml              # field_continents
│   └── Distance.xml               # field_latitude, field_longitude, field_radius
└── Services.yaml                  # Symfony DI configuration (GEOIP_* env wiring)
```

## Setup

Changes here require a TYPO3 cache flush to take effect (`ddev exec vendor/bin/typo3 cache:flush` inside an installed DDEV site, or Admin Tools → Flush Cache).

## Build & Tests

```bash
composer ci:test:php:phpstan     # covers TCA/Overrides PHP
composer ci:test:php:functional  # loads the extension incl. this configuration
```

## Code Style & Conventions

### Registering Geolocation Context Types

Registration goes through the base extension's API, not raw TCA manipulation:

```php
// TCA/Overrides/tx_contexts_contexts.php
use Netresearch\Contexts\Api\Configuration;

Configuration::registerContextType(
    'geolocation_country',
    'LLL:EXT:contexts_geolocation/Resources/Private/Language/locallang.xlf:context.type.country',
    CountryContext::class,
    'FILE:EXT:contexts_geolocation/Configuration/FlexForms/Country.xml',
);
```

### FlexForm for GeoIP Configuration

```xml
<!-- Country selection, continent selection, distance calculation -->
<field_countries>
    <label>Countries</label>
    <config>
        <type>select</type>
        <renderType>selectCheckBox</renderType>
    </config>
</field_countries>
```

### Services.yaml

The adapter binding takes `$databasePath: '%env(GEOIP_DATABASE_PATH)%'`;
`GeoLocationService` takes `$trustProxyHeaders: '%env(bool:GEOIP_TRUST_PROXY_HEADERS)%'`
and is `public: true` because context types fetch it from the container at runtime.

## Security

- Never hardcode database paths or credentials — configuration comes from `GEOIP_*` environment variables
- `Dto/`, `Exception/` (and `Domain/Model/` if introduced) are excluded from DI autowiring; keep it that way

## PR/Commit Checklist

- [ ] Context types registered via `Configuration::registerContextType()`
- [ ] FlexForms have language file references
- [ ] Services.yaml changes tested with cache:flush

## Good vs Bad Examples

```php
// Good: register through the contexts API (single source of truth)
Configuration::registerContextType('geolocation_country', $label, CountryContext::class, $flexFile);

// Bad: append to $GLOBALS['TCA'] items directly — bypasses the base extension's registry
$GLOBALS['TCA']['tx_contexts_contexts']['columns']['type']['config']['items'][] = [...];
```

## House Rules

- All geolocation context types need registration in `TCA/Overrides/tx_contexts_contexts.php` + a FlexForm
- Use ISO country/continent codes in configuration
- Test configuration in both TYPO3 v12 and v13

## When Stuck

- Base extension API: https://github.com/netresearch/t3x-contexts
- TYPO3 FlexForms: https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/FlexForms/Index.html
- Symfony DI in TYPO3: https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/DependencyInjection/Index.html
