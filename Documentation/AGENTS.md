<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-01-28 -->

# AGENTS.md — Documentation/

RST documentation for docs.typo3.org publication.

## Overview

```
Documentation/
├── Index.rst              # Main entry point
├── guides.xml             # PHP-based rendering config
├── Introduction/          # Overview, features
├── Installation/          # Setup with GeoIP database
├── Configuration/         # GeoIP settings, database paths
└── ContextTypes/          # Geolocation context reference
```

## Build & Tests

```bash
# Render locally with Docker
docker run --rm \
    -v ./Documentation:/project/docs \
    ghcr.io/typo3-documentation/render-guides:latest
```

## Code Style & Conventions

### RST Formatting

- Sentence case headings
- Code blocks with language specified
- Cross-references with `:ref:`

### Geolocation-Specific Content

```rst
.. confval:: geoip.databasePath
   :type: string
   :Default: /var/lib/GeoIP/

   Path to MaxMind GeoIP2 database files.
```

## PR/Commit Checklist

- [ ] RST renders without warnings
- [ ] GeoIP setup instructions complete
- [ ] Cross-references resolve

## House Rules

- Output directory: `Documentation-GENERATED-temp/`
- Keep README.md synchronized with docs
