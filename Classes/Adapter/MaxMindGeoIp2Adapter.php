<?php

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Adapter;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;
use Netresearch\ContextsGeolocation\Dto\GeoLocation;
use Netresearch\ContextsGeolocation\Exception\GeoIpException;

/**
 * MaxMind GeoIP2 adapter for geolocation lookups.
 *
 * Uses MaxMind's GeoIP2 PHP library with GeoLite2-City or GeoIP2-City database.
 *
 * @see https://github.com/maxmind/GeoIP2-php
 */
final class MaxMindGeoIp2Adapter implements GeoIpAdapterInterface
{
    private ?Reader $reader = null;

    public function __construct(
        private readonly string $databasePath,
    ) {}

    public function lookup(string $ipAddress): ?GeoLocation
    {
        if (!$this->isAvailable()) {
            throw new GeoIpException(
                \sprintf('GeoIP2 database not available at path: %s', $this->databasePath),
            );
        }

        try {
            $record = $this->getReader()->city($ipAddress);

            return new GeoLocation(
                countryCode: $record->country->isoCode,
                countryCode3: null, // GeoIP2 doesn't provide ISO 3166-1 alpha-3
                countryName: $record->country->name,
                continentCode: $record->continent->code,
                continentName: $record->continent->name,
                latitude: $record->location->latitude,
                longitude: $record->location->longitude,
                city: $record->city->name,
                postalCode: $record->postal->code,
                region: $record->mostSpecificSubdivision->isoCode,
                regionName: $record->mostSpecificSubdivision->name,
            );
        } catch (AddressNotFoundException) {
            return null;
        } catch (InvalidDatabaseException $e) {
            throw new GeoIpException(
                \sprintf('Invalid GeoIP2 database: %s', $e->getMessage()),
                (int) $e->getCode(),
                $e,
            );
        }
    }

    public function isAvailable(): bool
    {
        return file_exists($this->databasePath) && is_readable($this->databasePath);
    }

    public function getCountryCode(string $ipAddress): ?string
    {
        try {
            $record = $this->getReader()->city($ipAddress);
            return $record->country->isoCode;
        } catch (AddressNotFoundException|InvalidDatabaseException) {
            return null;
        }
    }

    public function getCountryName(string $ipAddress): ?string
    {
        try {
            $record = $this->getReader()->city($ipAddress);
            return $record->country->name;
        } catch (AddressNotFoundException|InvalidDatabaseException) {
            return null;
        }
    }

    public function getContinentCode(string $ipAddress): ?string
    {
        try {
            $record = $this->getReader()->city($ipAddress);
            return $record->continent->code;
        } catch (AddressNotFoundException|InvalidDatabaseException) {
            return null;
        }
    }

    public function getLatitude(string $ipAddress): ?float
    {
        try {
            $record = $this->getReader()->city($ipAddress);
            return $record->location->latitude;
        } catch (AddressNotFoundException|InvalidDatabaseException) {
            return null;
        }
    }

    public function getLongitude(string $ipAddress): ?float
    {
        try {
            $record = $this->getReader()->city($ipAddress);
            return $record->location->longitude;
        } catch (AddressNotFoundException|InvalidDatabaseException) {
            return null;
        }
    }

    public function getCity(string $ipAddress): ?string
    {
        try {
            $record = $this->getReader()->city($ipAddress);
            return $record->city->name;
        } catch (AddressNotFoundException|InvalidDatabaseException) {
            return null;
        }
    }

    /**
     * Get the MaxMind Reader instance (lazy initialization).
     *
     * @throws GeoIpException If the database cannot be read
     */
    private function getReader(): Reader
    {
        if ($this->reader === null) {
            try {
                $this->reader = new Reader($this->databasePath);
            } catch (InvalidDatabaseException $e) {
                throw new GeoIpException(
                    \sprintf('Cannot read GeoIP2 database: %s', $e->getMessage()),
                    (int) $e->getCode(),
                    $e,
                );
            }
        }
        return $this->reader;
    }
}
