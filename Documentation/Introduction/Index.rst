.. include:: /Includes.rst.txt

.. _introduction:

============
Introduction
============

What does it do?
================

The Contexts Geolocation extension provides location-based context types for
the TYPO3 Contexts extension. It enables you to show or hide pages and content
elements based on the visitor's geographic location.

The extension determines the visitor's location by looking up their IP address
in a MaxMind GeoIP2 database. This provides country, continent, and approximate
coordinates for most public IP addresses.

Key features
============

Country-based content
   Show content only to visitors from specific countries. Useful for
   region-specific offers, legal compliance, or localized content.

Continent-based content
   Target entire continents for broader geographic segmentation. Perfect for
   global campaigns or continent-specific regulations.

Distance-based content
   Show content to visitors within a specific radius from a location. Ideal for
   local businesses, event promotions, or regional services.

Accurate IP geolocation
   Uses MaxMind GeoIP2 database for reliable country and continent detection.
   The free GeoLite2 database provides good accuracy for most use cases.

Use cases
=========

- **Legal compliance**: Show GDPR notices only to European visitors.
- **Regional pricing**: Display country-specific prices or currency.
- **Local promotions**: Advertise events to visitors within a certain radius.
- **Content licensing**: Restrict media content based on geographic licensing.
- **Language hints**: Suggest language based on visitor's country.

Accuracy considerations
=======================

IP-based geolocation has inherent limitations:

Country detection
   Generally very accurate (95%+ for major countries). Reliable for most
   business applications.

Continent detection
   Very accurate, as it derives from country detection.

City and coordinates
   Accuracy varies significantly. Often only accurate to the metropolitan
   area or region. Do not rely on precise coordinates.

Distance calculations
   Due to coordinate inaccuracy, distance-based contexts work best with
   larger radii (50+ km). Do not use for precise targeting.

.. note::

   Visitors using VPNs, proxies, or mobile networks may be geolocated to
   different locations than their actual physical position.

Requirements
============

- TYPO3 v12.4 LTS or v13.4 LTS.
- PHP 8.2 or higher.
- The `contexts <https://github.com/netresearch/t3x-contexts>`__ extension (v4.0+).
- MaxMind GeoLite2-City database (free registration required).

.. tip::

   For higher accuracy requirements, consider MaxMind's commercial GeoIP2
   databases which offer better precision and more frequent updates.
