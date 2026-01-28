# Contexts: Geolocation

[![Latest Stable Version](https://img.shields.io/packagist/v/netresearch/contexts-geolocation.svg?style=flat-square)](https://packagist.org/packages/netresearch/contexts-geolocation)
[![TYPO3](https://img.shields.io/badge/TYPO3-12.4%20|%2013.4-orange.svg?style=flat-square)](https://typo3.org/)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg?style=flat-square)](https://php.net/)
[![License](https://img.shields.io/badge/License-AGPL--3.0--or--later-green.svg?style=flat-square)](LICENSE)

Geolocation-based context types for TYPO3. Show pages and content elements for
visitors in specific countries, continents, or within a defined geographic area.

Uses [MaxMind GeoIP2](https://www.maxmind.com/en/geoip2-databases) for accurate
IP-based location detection.

## Features

- **Country context**: Target visitors from specific countries (ISO 3166-1 alpha-2 codes).
- **Continent context**: Target visitors from specific continents.
- **Distance context**: Target visitors within a radius from a geographic point.
- **MaxMind GeoIP2**: Uses the modern GeoIP2 library with GeoLite2 or commercial databases.
- **Session caching**: Efficient lookups with session-based caching.
- **Proxy support**: Configurable trust for X-Forwarded-For and similar headers.

## Requirements

- TYPO3 12.4 LTS or 13.4 LTS
- PHP 8.2 or higher
- [contexts](https://github.com/netresearch/t3x-contexts) extension (v4.0+)
- MaxMind GeoLite2-City database (free) or GeoIP2-City database (commercial)

## Installation

Install via Composer:

```bash
composer require netresearch/contexts-geolocation
```

Activate the extension:

```bash
vendor/bin/typo3 extension:activate contexts_geolocation
```

## MaxMind GeoLite2 Database Setup

This extension requires a MaxMind GeoIP2 database for IP geolocation.

### 1. Create a MaxMind Account

Sign up for a free account at [MaxMind GeoLite2 Signup](https://www.maxmind.com/en/geolite2/signup).

### 2. Download the Database

Download "GeoLite2 City" in MMDB format from your MaxMind account and extract it
to a location on your server (e.g., `/var/lib/GeoIP/GeoLite2-City.mmdb`).

Alternatively, use the `geoipupdate` tool for automatic updates:

```bash
# Install on Debian/Ubuntu
apt-get install geoipupdate

# Configure /etc/GeoIP.conf with your credentials
# Run update
geoipupdate
```

### 3. Configure the Database Path

Set the environment variable:

```bash
# In your .env file
GEOIP_DATABASE_PATH=/var/lib/GeoIP/GeoLite2-City.mmdb
```

## Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `GEOIP_DATABASE_PATH` | Path to the GeoIP2 database file | (required) |
| `GEOIP_TRUST_PROXY_HEADERS` | Trust X-Forwarded-For headers | `false` |

### Proxy Configuration

If your TYPO3 installation is behind a reverse proxy, enable proxy header trust:

```bash
GEOIP_TRUST_PROXY_HEADERS=true
```

## Context Types

### Continent Context

Matches visitors based on their continent:

- AF: Africa
- AN: Antarctica
- AS: Asia
- EU: Europe
- NA: North America
- OC: Oceania
- SA: South America

### Country Context

Matches visitors based on their country using ISO 3166-1 alpha-2 codes
(e.g., DE, US, FR, GB).

### Distance Context

Matches visitors within a specified radius (in kilometers) from a geographic
point defined by latitude and longitude.

**Note**: Distance-based targeting works best with larger radii (50+ km) due to
the inherent limitations of IP-based geolocation accuracy.

## Accuracy Considerations

IP-based geolocation has inherent limitations:

- **Country detection**: Generally very accurate (95%+).
- **Continent detection**: Very accurate (derived from country).
- **City/coordinates**: Accuracy varies significantly; often only accurate to
  the metropolitan area.

Visitors using VPNs, proxies, or mobile networks may be geolocated to different
locations than their actual physical position.

## Migration from v1.x

Version 2.0 is a complete rewrite with breaking changes:

- **New GeoIP library**: Uses MaxMind GeoIP2 instead of the legacy PECL geoip
  extension or PEAR Net_GeoIP.
- **Environment configuration**: Database path is now configured via environment
  variables instead of extension settings.
- **PHP 8.2+ required**: Modern PHP features and strict typing.
- **TYPO3 12.4+ required**: Drops support for TYPO3 11 and earlier.

## Documentation

Full documentation is available at [docs.typo3.org](https://docs.typo3.org/p/netresearch/contexts-geolocation/main/en-us/)
(once published) or in the `Documentation/` folder of this extension.

## Contributing

Contributions are welcome! Please see our [Contributing Guide](https://github.com/netresearch/t3x-contexts_geolocation/blob/main/CONTRIBUTING.md).

## License

This extension is licensed under the [AGPL-3.0-or-later](LICENSE).

## Credits

Developed and maintained by [Netresearch DTT GmbH](https://www.netresearch.de/).

- **Website**: https://www.netresearch.de/
- **GitHub**: https://github.com/netresearch/t3x-contexts_geolocation
- **Issues**: https://github.com/netresearch/t3x-contexts_geolocation/issues
