<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-08-19 -->

# AGENTS.md — Documentation/

RST documentation for docs.typo3.org publication.

## Overview

```
Documentation/
├── Index.rst              # Main entry point
├── Sitemap.rst            # Sitemap
├── Includes.rst.txt       # Shared includes
├── guides.xml             # PHP-based rendering config
├── Introduction/          # Overview, features
├── Installation/          # Setup with GeoIP database
├── Configuration/         # GEOIP_* environment variables
└── ContextTypes/          # Geolocation context reference (FlexForm confvals)
```

## Setup

No local install needed — rendering runs in the official Docker image (see below).
Keep `guides.xml` as the single rendering configuration.

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

Configuration values are documented with `confval` directives; the existing ones
are `GEOIP_DATABASE_PATH` / `GEOIP_TRUST_PROXY_HEADERS` (Configuration/Index.rst)
and the FlexForm fields (`field_countries`, `field_continents`, `field_latitude`,
`field_longitude`, `field_radius`) in ContextTypes/Index.rst:

```rst
.. confval:: GEOIP_DATABASE_PATH
   :name: confval-geoip-database-path
   :type: string

   Path to the MaxMind GeoIP2 database file.
```

## Security

- Never include real MaxMind license keys or account IDs in examples
- Use documentation IP ranges (192.0.2.0/24, 2001:db8::/32) in examples, never real visitor IPs

## PR/Commit Checklist

- [ ] RST renders without warnings
- [ ] GeoIP setup instructions complete
- [ ] Cross-references resolve

## Good vs Bad Examples

```rst
.. Good: language-tagged code block with env-based config
.. code-block:: bash

   GEOIP_DATABASE_PATH=/var/lib/GeoIP/GeoLite2-City.mmdb

.. Bad: bare literal block documenting a config mechanism that does not exist
::

   $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['contexts_geolocation']['databasePath'] = ...
```

## House Rules

- Output directory: `Documentation-GENERATED-temp/`
- Keep README.md synchronized with docs
- `Documentation/CLAUDE.md` is a regular file, not a symlink — the docs renderer rejects symlinks

## When Stuck

- TYPO3 documentation guide: https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/
- render-guides: https://github.com/TYPO3-Documentation/render-guides
- Extension issues: https://github.com/netresearch/t3x-contexts_geolocation/issues
