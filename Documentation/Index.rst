.. include:: /Includes.rst.txt

.. _start:

======================
Contexts: Geolocation
======================

:Extension key:
   contexts_geolocation

:Package name:
   netresearch/contexts-geolocation

:Version:
   |release|

:Language:
   en

:Author:
   Netresearch DTT GmbH

:License:
   This document is published under the
   `Creative Commons BY 4.0 <https://creativecommons.org/licenses/by/4.0/>`__
   license.

:Rendered:
   |today|

----

Geolocation-based context types for TYPO3. Show pages and content elements for
visitors in specific countries, continents, or within a defined geographic area.
Uses MaxMind GeoIP2 for accurate IP-based location detection.

.. versionadded:: 2.0.0
   Complete rewrite for TYPO3 12.4/13.4 LTS. Now uses MaxMind GeoIP2 library
   instead of legacy PECL geoip extension.

----

.. card-grid::
   :columns: 1
   :columns-md: 2
   :gap: 4
   :class: pb-4
   :card-height: 100

   .. card:: :ref:`Introduction <introduction>`

      Learn what the Geolocation extension does and how it enables
      location-based content delivery in TYPO3.

   .. card:: :ref:`Installation <installation>`

      Install the extension and set up the MaxMind GeoLite2 database.

   .. card:: :ref:`Configuration <configuration>`

      Configure the GeoIP adapter and environment variables.

   .. card:: :ref:`Context types <context-types>`

      Explore the three geolocation context types: Continent, Country,
      and Distance.

.. toctree::
   :maxdepth: 2
   :titlesonly:
   :hidden:

   Introduction/Index
   Installation/Index
   Configuration/Index
   ContextTypes/Index

----

**Credits**

Developed and maintained by `Netresearch DTT GmbH <https://www.netresearch.de/>`__.

.. Meta Menu

.. toctree::
   :hidden:

   Sitemap
