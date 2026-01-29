<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-01-28 -->

# AGENTS.md — Configuration/

TYPO3 configuration files for the Contexts Geolocation extension.

## Overview

```
Configuration/
├── TCA/                  # Table Configuration Array
│   └── Overrides/        # TCA overrides for context registration
├── FlexForms/            # Dynamic form configurations
│   └── ContextType/      # Per-type configuration forms
├── Services.yaml         # Symfony DI configuration
└── Icons.php             # Icon registry
```

## Code Style & Conventions

### Registering Geolocation Context Types

```php
// TCA/Overrides/tx_contexts_contexts.php
$GLOBALS['TCA']['tx_contexts_contexts']['columns']['type']['config']['items'][] = [
    'label' => 'LLL:EXT:contexts_geolocation/Resources/Private/Language/locallang_db.xlf:...',
    'value' => \Netresearch\ContextsGeolocation\Context\Type\GeolocationContext::class,
];
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

## PR/Commit Checklist

- [ ] Context types registered in TCA/Overrides
- [ ] FlexForms have language file references
- [ ] Services.yaml changes tested with cache:flush

## House Rules

- All geolocation context types need TCA registration + FlexForm
- Use ISO country/continent codes in configuration
- Test configuration in both TYPO3 v12 and v13
