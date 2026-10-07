.. SPDX-License-Identifier: CC-BY-4.0
.. SPDX-FileCopyrightText: Netresearch DTT GmbH

.. include:: /Includes.rst.txt

.. _configuration:

=============
Configuration
=============

This section covers configuration of the Contexts Geolocation extension,
including database setup, service configuration, and environment variables.

.. toctree::
   :maxdepth: 2
   :titlesonly:

   GeoIP

.. _configuration-environment:

Environment variables
=====================

The extension is configured primarily through environment variables, which
allows for different configurations per environment (development, staging,
production).

.. confval:: GEOIP_DATABASE_PATH
   :name: confval-geoip-database-path
   :type: string
   :Default: (none)

   Absolute path to the MaxMind GeoIP2 database file (MMDB format).

   **Required.** The extension will not function without a valid database.

   Example values:

   - ``/var/lib/GeoIP/GeoLite2-City.mmdb`` (typical Linux location)
   - ``/usr/share/GeoIP/GeoLite2-City.mmdb`` (alternative location)
   - ``/app/data/GeoLite2-City.mmdb`` (Docker/container location)

   .. code-block:: bash

      # In .env file
      GEOIP_DATABASE_PATH=/var/lib/GeoIP/GeoLite2-City.mmdb

.. _configuration-services:

Service configuration
=====================

The extension uses TYPO3's dependency injection for service configuration.
The default configuration in :file:`Configuration/Services.yaml` sets up
the services automatically.

For advanced use cases, you can override the service configuration:

.. code-block:: yaml
   :caption: Configuration/Services.yaml (custom)

   services:
     Netresearch\ContextsGeolocation\Adapter\GeoIpAdapterInterface:
       class: Netresearch\ContextsGeolocation\Adapter\MaxMindGeoIp2Adapter
       arguments:
         $databasePath: '/custom/path/to/GeoLite2-City.mmdb'

.. _configuration-proxy-headers:

Reverse proxy configuration
===========================

The extension uses the client IP address that TYPO3 determines for the
request. Behind a reverse proxy (nginx, Varnish, a load balancer), configure
TYPO3 itself, so that TYPO3 reads the client address from
``X-Forwarded-For`` only when the request comes from that proxy:

.. code-block:: php
   :caption: config/system/additional.php

   $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'] = '192.0.2.10';
   $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyHeaderMultiValue'] = 'first';

See `reverseProxyIP <https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/Configuration/Typo3ConfVars/SYS.html#confval-globals-typo3-conf-vars-sys-reverseproxyip>`__
in the TYPO3 core documentation for the format and the related settings.

A CDN that sends the client address in its own header (for example
``CF-Connecting-IP`` or ``True-Client-IP``) has to be mapped to
``X-Forwarded-For`` or to the connection address by the web server in front
of TYPO3.

.. versionchanged:: 2.0.0
   The ``GEOIP_TRUST_PROXY_HEADERS`` environment variable was removed. Proxy
   headers are evaluated through the TYPO3 reverse proxy configuration.

.. _configuration-caching:

Caching considerations
======================

Geolocation contexts affect page caching. The extension integrates with the
base Contexts extension's caching mechanism.

For optimal performance:

1. **Use appropriate cache lifetimes**: Geographic location rarely changes,
   so longer cache times are usually acceptable.

2. **Configure your CDN**: If using a CDN like Cloudflare or Fastly, ensure
   it varies cache by the ``X-Forwarded-For`` header or disable caching for
   geolocation-dependent pages.

3. **Reverse proxy configuration**: Configure your reverse proxy to vary
   cache entries by client IP or country.

Example Varnish configuration:

.. code-block:: none

   sub vcl_hash {
       # Include client IP in cache hash for geolocation-dependent pages
       if (req.http.X-Geo-Context) {
           hash_data(client.ip);
       }
   }

.. _configuration-private-ips:

Private IP handling
===================

Private and reserved IP addresses (localhost, LAN addresses) cannot be
geolocated and will result in contexts not matching. This includes:

- ``127.0.0.0/8`` (loopback)
- ``10.0.0.0/8`` (private)
- ``172.16.0.0/12`` (private)
- ``192.168.0.0/16`` (private)
- ``::1`` (IPv6 loopback)
- ``fc00::/7`` (IPv6 unique local)

.. tip::

   For local development and testing, use the debugging plugin or test with
   real public IP addresses to verify your context configurations work
   correctly.
