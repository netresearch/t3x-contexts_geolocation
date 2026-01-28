.. include:: /Includes.rst.txt

.. _context-types:

=============
Context types
=============

The Contexts Geolocation extension provides three context types for
location-based content targeting.

.. _context-types-continent:

Continent context
=================

Matches visitors based on their continent.

Configuration
-------------

.. confval:: field_continents
   :name: confval-continent-field-continents
   :type: string
   :Default: (none)

   Comma-separated list of continent codes to match.

   Available continent codes:

   .. csv-table::
      :header: "Code", "Continent"
      :widths: 20, 40

      "AF", "Africa"
      "AN", "Antarctica"
      "AS", "Asia"
      "EU", "Europe"
      "NA", "North America"
      "OC", "Oceania"
      "SA", "South America"

Example
-------

To create a context that matches visitors from Europe and North America:

1. Go to :guilabel:`Admin Tools > Contexts`.
2. Create a new context.
3. Select type "Geolocation: Continent".
4. In the configuration, select "Europe" and "North America".
5. Save the context.

.. tip::

   Use the "Invert match" option to match visitors who are NOT in the
   selected continents.

.. _context-types-country:

Country context
===============

Matches visitors based on their country.

Configuration
-------------

.. confval:: field_countries
   :name: confval-country-field-countries
   :type: string
   :Default: (none)

   Comma-separated list of ISO 3166-1 alpha-2 country codes to match.

   Common examples:

   .. csv-table::
      :header: "Code", "Country"
      :widths: 20, 40

      "DE", "Germany"
      "AT", "Austria"
      "CH", "Switzerland"
      "US", "United States"
      "GB", "United Kingdom"
      "FR", "France"
      "ES", "Spain"
      "IT", "Italy"

   The full list of country codes is available at
   `ISO 3166-1 alpha-2 <https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2>`__.

Example
-------

To create a context for DACH region (Germany, Austria, Switzerland):

1. Go to :guilabel:`Admin Tools > Contexts`.
2. Create a new context.
3. Select type "Geolocation: Country".
4. Select countries: Germany, Austria, Switzerland.
5. Save the context.

Use cases
---------

- **Legal compliance**: GDPR notices for EU countries.
- **Language targeting**: German content for DE, AT, CH.
- **Regional offers**: Country-specific pricing or promotions.
- **Content licensing**: Restrict media based on geographic rights.

.. _context-types-distance:

Distance context
================

Matches visitors within a specified radius from a geographic point.

Configuration
-------------

.. confval:: field_latitude
   :name: confval-distance-field-latitude
   :type: float
   :Default: (none)

   Latitude of the center point in decimal degrees.
   Range: -90.0 to 90.0.

.. confval:: field_longitude
   :name: confval-distance-field-longitude
   :type: float
   :Default: (none)

   Longitude of the center point in decimal degrees.
   Range: -180.0 to 180.0.

.. confval:: field_radius
   :name: confval-distance-field-radius
   :type: float
   :Default: (none)

   Radius in kilometers from the center point.

Technical details
-----------------

The distance is calculated using the Haversine formula, which computes the
great-circle distance between two points on a sphere. This provides accurate
results for any distance on Earth.

.. important::

   Due to the inherent inaccuracy of IP-based geolocation for coordinates,
   the distance context works best with larger radii (50+ km). For small
   radii, consider that the detected coordinates may be off by 10-50 km
   or more, especially for mobile users.

Example
-------

To create a context for visitors within 100 km of Leipzig, Germany:

1. Go to :guilabel:`Admin Tools > Contexts`.
2. Create a new context.
3. Select type "Geolocation: Distance".
4. Enter coordinates:

   - Latitude: 51.3397
   - Longitude: 12.3731
   - Radius: 100

5. Save the context.

Use cases
---------

- **Local events**: Promote events to nearby visitors.
- **Store locator**: Highlight nearest store location.
- **Regional services**: Show local service providers.
- **Delivery zones**: Display delivery availability.

.. warning::

   The free GeoLite2 database provides limited coordinate accuracy.
   For business-critical distance-based targeting, consider MaxMind's
   commercial GeoIP2 databases which offer better precision.

.. _context-types-common:

Common features
===============

All geolocation context types share these features:

Invert match
   All context types support the "Invert match" option from the base Contexts
   extension. When enabled, the context matches when the condition is NOT met.

Session caching
   Geolocation lookups are cached in the user session to avoid repeated
   database lookups during a single visit.

Private IP handling
   Private and local IP addresses (localhost, LAN) will never match
   geolocation contexts. This ensures consistent behavior during development
   while preventing false matches in production.

Dependencies
   Geolocation contexts can be combined with other contexts using the
   "Combination" context type from the base extension. For example, create
   a context that matches "European visitors on mobile devices".
