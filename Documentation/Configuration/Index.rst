.. include:: /Includes.rst.txt

.. _configuration:

=============
Configuration
=============

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

.. confval:: GEOIP_TRUST_PROXY_HEADERS
   :name: confval-geoip-trust-proxy-headers
   :type: boolean
   :Default: false

   Whether to trust proxy headers (``X-Forwarded-For``, ``X-Real-IP``) for
   client IP detection.

   Enable this only if your TYPO3 installation is behind a trusted reverse
   proxy (nginx, Varnish, load balancer).

   **Security warning:** Do not enable this if clients can connect directly
   to your server, as they could spoof their IP address.

   .. code-block:: bash

      # In .env file
      GEOIP_TRUST_PROXY_HEADERS=true

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

     Netresearch\ContextsGeolocation\Service\GeoLocationService:
       arguments:
         $trustProxyHeaders: true
         $proxyHeaders: ['X-Forwarded-For', 'X-Real-IP', 'CF-Connecting-IP']

.. _configuration-proxy-headers:

Proxy header configuration
==========================

When running behind a reverse proxy, the extension needs to know the real
client IP address. The following proxy headers are checked by default:

1. ``X-Forwarded-For`` - Standard proxy header (leftmost IP is the client).
2. ``X-Real-IP`` - Common alternative used by nginx.

For specific proxy setups, you may need to configure additional headers:

.. csv-table:: Common proxy headers
   :header: "Proxy/CDN", "Header"
   :widths: 40, 40

   "nginx", "X-Real-IP"
   "Cloudflare", "CF-Connecting-IP"
   "AWS ALB/ELB", "X-Forwarded-For"
   "Fastly", "Fastly-Client-IP"
   "Akamai", "True-Client-IP"

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
