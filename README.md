# Geolocation contexts

[![Latest Stable Version](https://img.shields.io/endpoint?url=https://typo3-badges.dev/badge/contexts_geolocation/version/shields)](https://extensions.typo3.org/extension/contexts_geolocation)
[![Total Downloads](https://img.shields.io/endpoint?url=https://typo3-badges.dev/badge/contexts_geolocation/downloads/shields)](https://extensions.typo3.org/extension/contexts_geolocation)
[![License](https://img.shields.io/badge/license-AGPL--3.0--or--later-blue.svg)](LICENSE)
[![TYPO3 Versions](https://img.shields.io/endpoint?url=https://typo3-badges.dev/badge/contexts_geolocation/typo3/shields)](https://extensions.typo3.org/extension/contexts_geolocation)

Allows you to show pages and content elements for people in certain
countries, continents or within a given area.

It uses the [Maxmind GeoIP](http://www.maxmind.com/en/geolocation_landing) database to determine the user's probable
physical location.
Note that this is working most of the time, but also fails regularly.

Country and Continent rules are pretty safe to use, while small distances
are not accurate at all.

## Setup

1. Install the PHP extension `geoip` (requires root access to the server)

   If that is not possible, install the `Net_GeoIP` package from PEAR
2. Install and activate this TYPO3 extension `contexts_geolocation`

## Dependencies

- `contexts` TYPO3 extension
- `static_info_tables` TYPO3 extension
- `geoip` PHP extension, see [its homepage](http://pecl.php.net/package/geoip)
- or [Net_GeoIP](http://pear.php.net/package/Net_GeoIP) from PEAR instead of `geoip`

## Context types

The `contexts_geolocation` extension ships with three different
context types:

### Continent

Matches when the user is on one of the selected continents.

Continent `- unknown -` can be used to match all users whose position
could not be determined.

### Country

Matches when the user accessing the page is in one of the selected countries.

Country `- unknown -` can be used to match all users whose position
could not be determined.

### Distance

Select a location on a map and a distance, and the context matches when
the user's location is within this area.

Note that the free geoip database does seldom give you exact locations,
so this is more a fun experiment rather that it should be used in production.

![Distance Configuration](doc/cfg-distance.png)

## Plugins

### Contexts geolocation: show current position

Shows a map with a marker at the detected position of the user's IP.

Also shows a dump of other data for this IP, like continent, country and city.

Useful for testing and debugging.

Allows manual IP address input.
