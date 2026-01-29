.. include:: /Includes.rst.txt

.. _configuration-geoip:

======================
MaxMind GeoIP2 Database
======================

The Contexts Geolocation extension requires a MaxMind GeoIP2 database to perform
IP-based geolocation lookups. This guide covers obtaining, installing, and
maintaining the database.

.. _configuration-geoip-obtaining:

Obtaining the Database
======================

.. _configuration-geoip-maxmind-account:

Create a MaxMind Account
------------------------

The free GeoLite2 database requires a MaxMind account:

1. Go to `MaxMind GeoLite2 Signup <https://www.maxmind.com/en/geolite2/signup>`__.
2. Enter your email address and create a password.
3. Accept the terms of service and EULA.
4. Confirm your email address by clicking the link in the confirmation email.

.. note::

   The free GeoLite2 database is suitable for most use cases. For higher
   accuracy requirements (e.g., precise city-level geolocation or VPN detection),
   consider upgrading to MaxMind's commercial GeoIP2 database offerings.

.. _configuration-geoip-license-key:

Generate a License Key
----------------------

After creating your account, generate a license key for automated database updates:

1. Log in to your MaxMind account at https://www.maxmind.com/en/account.
2. Navigate to :guilabel:`Account > Manage License Keys`.
3. Click :guilabel:`Generate new license key`.
4. Optionally provide a description (e.g., "TYPO3 Contexts Geolocation").
5. Save the license key securely. **The key is displayed only once.**

.. warning::

   Treat your license key like a password. Do not commit it to version control
   or share it publicly. Store it in a secure environment variable or secret
   management system.

.. _configuration-geoip-download:

Download Options
----------------

There are two ways to obtain the GeoLite2-City database:

**Option A: Manual Download**

For one-time setup or if you prefer to manage updates manually:

1. Log in to your MaxMind account.
2. Go to :guilabel:`Download Databases`.
3. Find "GeoLite2 City" in the list.
4. Click the download button next to the MMDB format (not the CSV format).
5. The file is compressed; extract the ``.mmdb`` file.
6. Copy to your server at the recommended location:

   .. code-block:: bash

      /var/lib/GeoIP/GeoLite2-City.mmdb

**Option B: Automatic Updates with geoipupdate**

For production environments where the database should be automatically updated:

1. Install the ``geoipupdate`` tool on your server.
2. Configure it with your license key.
3. Set up a cron job to run updates regularly.

See :ref:`configuration-geoip-auto-updates` for detailed instructions.

.. _configuration-geoip-installation:

Installation
============

.. _configuration-geoip-database-location:

Database File Location
----------------------

After obtaining the database, place it at a location accessible to your web server.
The recommended location depends on your setup:

**Linux/Unix (most common)**

.. code-block:: bash

   # Standard location
   /var/lib/GeoIP/GeoLite2-City.mmdb

   # Alternative location
   /usr/share/GeoIP/GeoLite2-City.mmdb

**Docker/Container**

.. code-block:: bash

   # Inside container
   /app/data/GeoLite2-City.mmdb

   # Or mount as volume
   /opt/geoip/GeoLite2-City.mmdb

**Shared hosting**

.. code-block:: bash

   # In your application directory (if /var/lib is not accessible)
   /home/username/public_html/data/GeoLite2-City.mmdb

Ensure the file has appropriate permissions:

.. code-block:: bash

   chmod 644 /var/lib/GeoIP/GeoLite2-City.mmdb
   chown www-data:www-data /var/lib/GeoIP/GeoLite2-City.mmdb

.. note::

   The directory must exist and be readable by the web server process. Use
   ``chown`` to set the owner to your web server user (typically ``www-data``,
   ``apache``, or ``nginx``).

.. _configuration-geoip-extension-configuration:

Configure the Extension
-----------------------

Point the extension to your database using the environment variable:

.. code-block:: bash

   # In your .env file
   GEOIP_DATABASE_PATH=/var/lib/GeoIP/GeoLite2-City.mmdb

Or configure in :file:`Configuration/Services.yaml`:

.. code-block:: yaml
   :caption: Configuration/Services.yaml (advanced)

   services:
     Netresearch\ContextsGeolocation\Adapter\GeoIpAdapterInterface:
       class: Netresearch\ContextsGeolocation\Adapter\MaxMindGeoIp2Adapter
       arguments:
         $databasePath: '/var/lib/GeoIP/GeoLite2-City.mmdb'

.. important::

   The ``GEOIP_DATABASE_PATH`` environment variable is **required**. The extension
   will not function without it. Verify the path is correct and the file exists:

   .. code-block:: bash

      # Test the path
      test -f /var/lib/GeoIP/GeoLite2-City.mmdb && echo "Database found" || echo "Database NOT found"

.. _configuration-geoip-typo3-example:

TYPO3 Configuration Example
----------------------------

A complete TYPO3 setup using Docker:

.. code-block:: yaml
   :caption: docker-compose.yml (example)

   version: '3'
   services:
     web:
       image: typo3/cms-apache:12
       volumes:
         - ./var/lib/GeoIP:/var/lib/GeoIP:ro
       environment:
         - GEOIP_DATABASE_PATH=/var/lib/GeoIP/GeoLite2-City.mmdb
         - GEOIP_TRUST_PROXY_HEADERS=true
       ports:
         - "80:80"

And in your ``.env`` file:

.. code-block:: bash
   :caption: .env (example)

   # GeoIP Configuration
   GEOIP_DATABASE_PATH=/var/lib/GeoIP/GeoLite2-City.mmdb
   GEOIP_TRUST_PROXY_HEADERS=false

.. _configuration-geoip-auto-updates:

Automatic Updates
=================

For production environments, automate database updates to ensure accuracy.
The ``geoipupdate`` tool manages this automatically.

.. _configuration-geoip-install-tool:

Install geoipupdate
-------------------

**Debian/Ubuntu**

.. code-block:: bash

   # Add MaxMind's repository
   wget https://dev.maxmind.com/static/maxmind-db/geoipupdate/geoipupdate_7.0.1_linux_amd64.deb
   apt install ./geoipupdate_7.0.1_linux_amd64.deb

   # Or via apt-get (if available in your distribution)
   apt-get update
   apt-get install geoipupdate

**RedHat/CentOS**

.. code-block:: bash

   rpm -ivh https://dev.maxmind.com/static/maxmind-db/geoipupdate/geoipupdate_7.0.1_linux_amd64.rpm

**macOS**

.. code-block:: bash

   brew install geoipupdate

**From source**

.. code-block:: bash

   # Download latest release
   wget https://github.com/maxmind/geoipupdate/releases/download/v7.0.1/geoipupdate_7.0.1_linux_amd64.tar.gz
   tar xvzf geoipupdate_7.0.1_linux_amd64.tar.gz
   sudo install -m 0755 geoipupdate/geoipupdate /usr/local/bin

.. _configuration-geoip-configure-geoipupdate:

Configure geoipupdate
---------------------

Edit the ``geoipupdate`` configuration file:

.. code-block:: bash
   :caption: /etc/GeoIP.conf (configuration)

   # MaxMind account information
   AccountID YOUR_ACCOUNT_ID
   LicenseKey YOUR_LICENSE_KEY

   # Database editions to download
   EditionIDs GeoLite2-City

   # Download directory
   DatabaseDirectory /var/lib/GeoIP

The following options are commonly used:

.. csv-table:: geoipupdate configuration options
   :header: "Option", "Description", "Example"
   :widths: 25, 50, 25

   "AccountID", "Your MaxMind account ID", "123456"
   "LicenseKey", "Your license key (from account)", "AbCdEfGhIjK..."
   "EditionIDs", "Databases to download (space-separated)", "GeoLite2-City"
   "DatabaseDirectory", "Where to save databases", "/var/lib/GeoIP"
   "Frequency", "Check for updates (daily or weekly)", "7"

.. important::

   Replace ``YOUR_ACCOUNT_ID`` and ``YOUR_LICENSE_KEY`` with your actual
   credentials. Secure the file with proper permissions:

   .. code-block:: bash

      chmod 600 /etc/GeoIP.conf

.. _configuration-geoip-test-update:

Test the Update
---------------

Test that ``geoipupdate`` works correctly:

.. code-block:: bash

   # Run an update manually
   sudo geoipupdate

   # Verify the database was downloaded
   ls -lh /var/lib/GeoIP/GeoLite2-City.mmdb

The output should show a file larger than 30 MB.

.. _configuration-geoip-cron-setup:

Cron Job Setup
--------------

Set up automatic updates using a cron job. Edit your crontab:

.. code-block:: bash

   sudo crontab -e

Add one of the following lines:

.. code-block:: bash
   :caption: Run update weekly (Mondays at 2 AM)

   0 2 * * 1 /usr/bin/geoipupdate

.. code-block:: bash
   :caption: Run update twice weekly (Mondays and Thursdays at 2 AM)

   0 2 * * 1,4 /usr/bin/geoipupdate

.. code-block:: bash
   :caption: Run update daily at 3 AM

   0 3 * * * /usr/bin/geoipupdate

Verify the cron job is scheduled:

.. code-block:: bash

   sudo crontab -l

.. tip::

   MaxMind releases database updates twice per week (typically Wednesday and
   Friday). Schedule updates for the following day to ensure you have the
   latest data.

.. _configuration-geoip-cron-logging:

Cron Job Logging
^^^^^^^^^^^^^^^^

Capture ``geoipupdate`` output for monitoring:

.. code-block:: bash
   :caption: Run with logging (Mondays at 2 AM)

   0 2 * * 1 /usr/bin/geoipupdate >> /var/log/geoipupdate.log 2>&1

Monitor the log file:

.. code-block:: bash

   tail -f /var/log/geoipupdate.log

.. _configuration-geoip-docker-auto-update:

Docker Auto-Update Example
---------------------------

Use a sidecar container or shell script in Docker to manage updates:

.. code-block:: yaml
   :caption: docker-compose.yml with geoipupdate service

   version: '3'
   services:
     geoipupdate:
       image: maxmindinc/geoipupdate
       environment:
         GEOIPUPDATE_ACCOUNT_ID: YOUR_ACCOUNT_ID
         GEOIPUPDATE_LICENSE_KEY: YOUR_LICENSE_KEY
         GEOIPUPDATE_EDITION_IDS: GeoLite2-City
         GEOIPUPDATE_FREQUENCY: 7
       volumes:
         - geoip_data:/usr/share/GeoIP
       networks:
         - default

     web:
       image: typo3/cms-apache:12
       depends_on:
         - geoipupdate
       volumes:
         - geoip_data:/var/lib/GeoIP:ro
       environment:
         - GEOIP_DATABASE_PATH=/var/lib/GeoIP/GeoLite2-City.mmdb
       ports:
         - "80:80"

   volumes:
     geoip_data:

Alternatively, use a shell script for updates:

.. code-block:: bash
   :caption: docker-geoipupdate.sh (shell script)

   #!/bin/bash
   # Run geoipupdate in Docker container

   docker run --rm \
     -e GEOIPUPDATE_ACCOUNT_ID=${GEOIPUPDATE_ACCOUNT_ID} \
     -e GEOIPUPDATE_LICENSE_KEY=${GEOIPUPDATE_LICENSE_KEY} \
     -e GEOIPUPDATE_EDITION_IDS=GeoLite2-City \
     -v /var/lib/GeoIP:/usr/share/GeoIP \
     maxmindinc/geoipupdate

Add to crontab:

.. code-block:: bash

   0 2 * * 1 /path/to/docker-geoipupdate.sh

.. _configuration-geoip-kubernetes:

Kubernetes Cronjob Example
^^^^^^^^^^^^^^^^^^^^^^^^^^

For Kubernetes deployments:

.. code-block:: yaml
   :caption: geoip-update-cronjob.yaml

   apiVersion: batch/v1
   kind: CronJob
   metadata:
     name: geoipupdate
   spec:
     schedule: "0 2 * * 1"  # Weekly, Monday 2 AM
     jobTemplate:
       spec:
         template:
           spec:
             containers:
             - name: geoipupdate
               image: maxmindinc/geoipupdate:latest
               env:
               - name: GEOIPUPDATE_ACCOUNT_ID
                 valueFrom:
                   secretKeyRef:
                     name: maxmind-credentials
                     key: account-id
               - name: GEOIPUPDATE_LICENSE_KEY
                 valueFrom:
                   secretKeyRef:
                     name: maxmind-credentials
                     key: license-key
               - name: GEOIPUPDATE_EDITION_IDS
                 value: "GeoLite2-City"
               volumeMounts:
               - name: geoip-data
                 mountPath: /usr/share/GeoIP
             volumes:
             - name: geoip-data
               persistentVolumeClaim:
                 claimName: geoip-data
             restartPolicy: OnFailure

.. _configuration-geoip-troubleshooting:

Troubleshooting
===============

.. _configuration-geoip-verify-database:

Verify Database is Loaded
--------------------------

Test that the extension can access and use the database:

**Command-line test**

.. code-block:: bash

   # Requires a public IP (won't work with localhost or private IPs)
   vendor/bin/typo3 contexts:geolocation:lookup 8.8.8.8

Expected output:

.. code-block:: json

   {
     "ip": "8.8.8.8",
     "country": "US",
     "continent": "NA",
     "latitude": 37.386,
     "longitude": -122.084
   }

**In TYPO3 backend**

1. Log in to the TYPO3 backend.
2. Go to :guilabel:`Admin Tools > Extensions`.
3. Search for "Contexts Geolocation".
4. If the extension is not loaded, activate it.

**Check the database file**

.. code-block:: bash

   # Verify file exists and is readable
   ls -lh /var/lib/GeoIP/GeoLite2-City.mmdb

   # Check file size (should be > 30 MB)
   du -h /var/lib/GeoIP/GeoLite2-City.mmdb

   # Check format (should contain MMDB magic bytes)
   file /var/lib/GeoIP/GeoLite2-City.mmdb

.. _configuration-geoip-common-issues:

Common Issues and Solutions
----------------------------

**Issue: Extension reports "Database not found"**

**Symptoms:** Geolocation contexts never match. TYPO3 logs show database
errors.

**Solution:**

1. Verify the environment variable is set:

   .. code-block:: bash

      echo $GEOIP_DATABASE_PATH

2. Ensure the file exists at the configured path:

   .. code-block:: bash

      test -f $GEOIP_DATABASE_PATH && echo "OK" || echo "NOT FOUND"

3. Check file permissions (must be readable by web server):

   .. code-block:: bash

      ls -l $GEOIP_DATABASE_PATH

4. Test the path in PHP:

   .. code-block:: php

      $path = getenv('GEOIP_DATABASE_PATH');
      var_dump(file_exists($path), is_readable($path), filesize($path));

**Issue: Geolocation always returns NULL or unknown**

**Symptoms:** The ``lookup`` command runs but returns no data. All contexts fail
to match.

**Solution:**

1. Verify the database file is valid:

   .. code-block:: bash

      file /var/lib/GeoIP/GeoLite2-City.mmdb
      # Output should contain "MaxMind DB"

2. Check that you're using a public IP (private IPs won't geolocate):

   .. code-block:: bash

      vendor/bin/typo3 contexts:geolocation:lookup 192.168.1.1
      # This will fail - use a public IP instead
      vendor/bin/typo3 contexts:geolocation:lookup 1.1.1.1
      # This should work (Cloudflare DNS)

3. Verify the database contains the "City" dataset:

   .. code-block:: bash

      # Check database metadata
      strings /var/lib/GeoIP/GeoLite2-City.mmdb | grep -i city

**Issue: geoipupdate fails with permission denied**

**Symptoms:** Cron job runs but no update occurs. Logs show permission errors.

**Solution:**

1. Ensure the download directory exists and is writable:

   .. code-block:: bash

      mkdir -p /var/lib/GeoIP
      chmod 755 /var/lib/GeoIP

2. Run ``geoipupdate`` with appropriate user (not root in production):

   .. code-block:: bash

      sudo -u www-data /usr/bin/geoipupdate

3. Check file permissions in ``/etc/GeoIP.conf``:

   .. code-block:: bash

      chmod 600 /etc/GeoIP.conf

4. Verify the cron job is running by checking system logs:

   .. code-block:: bash

      sudo tail -f /var/log/syslog | grep geoipupdate

**Issue: Database updates fail with "Invalid license key"**

**Symptoms:** ``geoipupdate`` command fails with authentication error.

**Solution:**

1. Verify the credentials in ``/etc/GeoIP.conf``:

   .. code-block:: bash

      sudo cat /etc/GeoIP.conf

2. Test credentials manually:

   .. code-block:: bash

      curl -v --user "YOUR_ACCOUNT_ID:YOUR_LICENSE_KEY" \
        https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&suffix=tar.gz

3. Regenerate your license key in MaxMind account settings (the old key may have
   expired or been revoked).

**Issue: Outdated database affects geolocation accuracy**

**Symptoms:** Locations don't match expected results. New IPs return no data.

**Solution:**

1. Check database age:

   .. code-block:: bash

      stat /var/lib/GeoIP/GeoLite2-City.mmdb | grep Modify

2. Force an immediate update:

   .. code-block:: bash

      sudo geoipupdate -v

3. Verify the cron job is running:

   .. code-block:: bash

      sudo crontab -l

4. Monitor updates:

   .. code-block:: bash

      sudo tail -f /var/log/geoipupdate.log

.. _configuration-geoip-performance:

Performance Considerations
==========================

IP lookups are typically very fast (< 1 ms per lookup). The extension caches
results in the user session to minimize repeated lookups:

**Session caching**

   The result for each IP address is cached in the user session for the session
   duration. This means repeat lookups during a single session are instant.

**Database in memory**

   For maximum performance in high-traffic environments, consider loading the
   database into memory using ``mmdb-compat`` or similar tools.

**Async geolocation (advanced)**

   For sites with thousands of concurrent users, consider asynchronous
   geolocation lookups using a background queue.

See the :ref:`Configuration <configuration>` section for additional performance
tuning options.

.. _configuration-geoip-privacy:

Privacy and Data Protection
============================

**IP addresses are personal data**

   Under GDPR and similar regulations, IP addresses are considered personal
   data. When using geolocation:

   - Document the lawful basis for IP processing in your privacy policy.
   - Only process IPs for the stated purpose (e.g., content delivery).
   - Implement appropriate technical and organizational measures.
   - Consider data retention policies.

**Log IP addresses carefully**

   Do not store raw IP addresses in logs without anonymization. Consider:

   - Anonymizing IPs by removing the last octet (e.g., ``192.168.1.0``).
   - Enabling log rotation and deletion after a reasonable retention period.
   - Using a VPN/proxy in development to avoid logging personal devices.

**Inform users**

   Your privacy notice should mention that you use geolocation for content
   delivery. Example text:

   > "We use your IP address to detect your approximate geographic location
   > and show you location-specific content. We do not store your IP address
   > for longer than necessary for this purpose."

For more information, see the MaxMind `Privacy and Data Protection`__
documentation.

.. __: https://www.maxmind.com/en/privacy/geoip

.. _configuration-geoip-further-reading:

Further Reading
===============

- `MaxMind GeoLite2 Documentation <https://dev.maxmind.com/geoip/geolite2-open-source-geolocation-database>`__
- `MaxMind geoipupdate Tool <https://github.com/maxmind/geoipupdate>`__
- `MaxMind API Documentation <https://dev.maxmind.com/>`__
- :ref:`configuration` - General extension configuration
- :ref:`context-types` - Available geolocation context types
