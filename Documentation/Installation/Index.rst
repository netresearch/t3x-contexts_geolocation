.. include:: /Includes.rst.txt

.. _installation:

============
Installation
============

.. _installation-requirements:

Requirements
============

.. csv-table:: Version compatibility
   :header: "Extension Version", "TYPO3", "PHP"
   :widths: 20, 30, 30

   "2.x", "12.4 LTS, 13.4 LTS", "8.2 - 8.5"
   "1.x (legacy)", "6.2 - 11.5", "5.4 - 8.1"

The recommended way to install this extension is via Composer.

.. _installation-composer:

Installation via Composer
=========================

.. code-block:: bash

   composer require netresearch/contexts-geolocation

After installation, activate the extension in the TYPO3 Extension Manager or
via CLI:

.. code-block:: bash

   vendor/bin/typo3 extension:activate contexts_geolocation

.. note::

   This extension requires the base ``contexts`` extension which will be
   installed automatically as a dependency.

.. _installation-geoip-database:

MaxMind GeoLite2 database setup
===============================

This extension requires a MaxMind GeoIP2 database for IP geolocation lookups.
The free GeoLite2-City database is sufficient for most use cases.

Step 1: Create a MaxMind account
--------------------------------

1. Go to `MaxMind GeoLite2 Signup <https://www.maxmind.com/en/geolite2/signup>`__.
2. Create a free account.
3. Confirm your email address.

Step 2: Generate a license key
------------------------------

1. Log in to your MaxMind account.
2. Go to :guilabel:`Account > Manage License Keys`.
3. Click :guilabel:`Generate new license key`.
4. Save the license key securely (it is shown only once).

Step 3: Download the database
-----------------------------

**Option A: Manual download**

1. Log in to your MaxMind account.
2. Go to :guilabel:`Download Databases`.
3. Download "GeoLite2 City" in MMDB format.
4. Extract the ``.mmdb`` file to your server.
5. Recommended location: :file:`/var/lib/GeoIP/GeoLite2-City.mmdb`.

**Option B: Automatic updates with geoipupdate**

MaxMind provides a tool for automatic database updates:

.. code-block:: bash

   # Install geoipupdate (Debian/Ubuntu)
   apt-get install geoipupdate

   # Configure /etc/GeoIP.conf
   AccountID YOUR_ACCOUNT_ID
   LicenseKey YOUR_LICENSE_KEY
   EditionIDs GeoLite2-City

   # Run update
   geoipupdate

   # Database is saved to /var/lib/GeoIP/GeoLite2-City.mmdb

.. tip::

   Set up a cron job to run ``geoipupdate`` weekly to keep the database current.
   GeoLite2 databases are updated twice per week.

Step 4: Configure the database path
-----------------------------------

Set the environment variable for the database path:

.. code-block:: bash

   # In your .env file or server configuration
   GEOIP_DATABASE_PATH=/var/lib/GeoIP/GeoLite2-City.mmdb

See the :ref:`Configuration <configuration>` section for more details.

.. _installation-database:

Database updates
================

After installation, run the database analyzer to create required tables:

.. code-block:: bash

   vendor/bin/typo3 database:updateschema

Or use the :guilabel:`Admin Tools > Maintenance > Analyze Database Structure`
module in the TYPO3 backend.

.. _installation-verification:

Verification
============

After installation, you should see:

1. New context types "Continent", "Country", and "Distance" in the context
   creation wizard.
2. The extension should be listed in :guilabel:`Admin Tools > Extensions`.

To verify the GeoIP database is working:

.. code-block:: bash

   # Test from the command line (requires a public IP)
   vendor/bin/typo3 contexts:geolocation:lookup 8.8.8.8

This should output the geolocation data for Google's public DNS server.
