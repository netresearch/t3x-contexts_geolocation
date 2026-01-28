<?php

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Adapter;

use Netresearch\ContextsGeolocation\Dto\GeoLocation;

/**
 * Interface for GeoIP adapters.
 *
 * Implementations provide geolocation data for IP addresses using different
 * data sources (MaxMind GeoIP2, etc.).
 */
interface GeoIpAdapterInterface
{
    /**
     * Look up geolocation data for an IP address.
     *
     * @param string $ipAddress IPv4 or IPv6 address
     * @return GeoLocation|null Null if IP cannot be resolved
     */
    public function lookup(string $ipAddress): ?GeoLocation;

    /**
     * Check if the adapter is available and configured.
     */
    public function isAvailable(): bool;

    /**
     * Get the country code (ISO 3166-1 alpha-2) for an IP address.
     *
     * @param string $ipAddress IPv4 or IPv6 address
     * @return string|null Two-letter country code or null if not found
     */
    public function getCountryCode(string $ipAddress): ?string;

    /**
     * Get the country name for an IP address.
     *
     * @param string $ipAddress IPv4 or IPv6 address
     * @return string|null Country name or null if not found
     */
    public function getCountryName(string $ipAddress): ?string;

    /**
     * Get the continent code for an IP address.
     *
     * @param string $ipAddress IPv4 or IPv6 address
     * @return string|null Continent code (AF, AN, AS, EU, NA, OC, SA) or null if not found
     */
    public function getContinentCode(string $ipAddress): ?string;

    /**
     * Get the latitude for an IP address.
     *
     * @param string $ipAddress IPv4 or IPv6 address
     * @return float|null Latitude or null if not found
     */
    public function getLatitude(string $ipAddress): ?float;

    /**
     * Get the longitude for an IP address.
     *
     * @param string $ipAddress IPv4 or IPv6 address
     * @return float|null Longitude or null if not found
     */
    public function getLongitude(string $ipAddress): ?float;

    /**
     * Get the city name for an IP address.
     *
     * @param string $ipAddress IPv4 or IPv6 address
     * @return string|null City name or null if not found
     */
    public function getCity(string $ipAddress): ?string;
}
