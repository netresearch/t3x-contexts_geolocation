<?php

/**
 * This file is part of the package netresearch/contexts-geolocation.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Context\Type;

use Netresearch\ContextsGeolocation\Service\GeoLocationService;

/**
 * Context type that matches based on visitor's distance from a point.
 *
 * Matches when the visitor's location (detected via GeoIP) is within
 * a configured radius from a central point. Uses the Haversine formula
 * for accurate distance calculation on a sphere.
 *
 * Configuration:
 * - field_latitude: Latitude of the center point (decimal degrees)
 * - field_longitude: Longitude of the center point (decimal degrees)
 * - field_radius: Radius in kilometers
 *
 * @author Netresearch DTT GmbH
 * @link https://www.netresearch.de
 */
class DistanceContext extends AbstractGeolocationContext
{
    /**
     * Earth's radius in kilometers.
     */
    private const EARTH_RADIUS_KM = 6371.0;

    /**
     * @param array<string, mixed> $arRow Database context row
     */
    public function __construct(array $arRow = [], ?GeoLocationService $geoLocationService = null)
    {
        parent::__construct($arRow, $geoLocationService);
    }

    /**
     * Check if the context matches the current request.
     *
     * @param array<int|string, mixed> $arDependencies Array of dependent context objects
     * @return bool True if the visitor is within the configured radius
     */
    public function match(array $arDependencies = []): bool
    {
        // Check session cache first
        [$bUseSession, $bMatch] = $this->getMatchFromSession();
        if ($bUseSession) {
            return $this->invert((bool) $bMatch);
        }

        // Get configuration
        $centerLatitude = $this->getConfValue('field_latitude');
        $centerLongitude = $this->getConfValue('field_longitude');
        $radius = $this->getConfValue('field_radius');

        // Validate configuration
        if (!$this->isValidConfiguration($centerLatitude, $centerLongitude, $radius)) {
            return $this->storeInSession($this->invert(false));
        }

        // Get client IP
        $clientIp = $this->getClientIpAddress();

        if ($clientIp === null) {
            return $this->storeInSession($this->invert(false));
        }

        // Skip private IPs
        if ($this->isPrivateIp($clientIp)) {
            return $this->storeInSession($this->invert(false));
        }

        // Get visitor coordinates from GeoIP
        $location = $this->getGeoLocationService()->getLocationForIp($clientIp);

        if ($location === null || !$location->hasCoordinates()) {
            return $this->storeInSession($this->invert(false));
        }

        $visitorLatitude = $location->latitude;
        $visitorLongitude = $location->longitude;

        if ($visitorLatitude === null || $visitorLongitude === null) {
            return $this->storeInSession($this->invert(false));
        }

        // Calculate distance using Haversine formula
        $distance = $this->calculateHaversineDistance(
            (float) $centerLatitude,
            (float) $centerLongitude,
            $visitorLatitude,
            $visitorLongitude,
        );

        // Check if within radius
        $bMatch = $distance <= (float) $radius;

        return $this->storeInSession($this->invert($bMatch));
    }

    /**
     * Calculate distance between two points using the Haversine formula.
     *
     * The Haversine formula determines the great-circle distance between
     * two points on a sphere given their latitudes and longitudes.
     *
     * @param float $lat1 Latitude of point 1 (decimal degrees)
     * @param float $lon1 Longitude of point 1 (decimal degrees)
     * @param float $lat2 Latitude of point 2 (decimal degrees)
     * @param float $lon2 Longitude of point 2 (decimal degrees)
     * @return float Distance in kilometers
     */
    public function calculateHaversineDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2,
    ): float {
        // Convert to radians
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        // Haversine formula
        $a = sin($deltaLat / 2) ** 2
            + cos($lat1Rad) * cos($lat2Rad) * sin($deltaLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_KM * $c;
    }

    /**
     * Validate that the configuration is complete and valid.
     */
    private function isValidConfiguration(
        string $latitude,
        string $longitude,
        string $radius,
    ): bool {
        // All values must be non-empty
        if ($latitude === '' || $longitude === '' || $radius === '') {
            return false;
        }

        // Latitude must be numeric and in range [-90, 90]
        if (!is_numeric($latitude)) {
            return false;
        }

        $lat = (float) $latitude;
        if ($lat < -90.0 || $lat > 90.0) {
            return false;
        }

        // Longitude must be numeric and in range [-180, 180]
        if (!is_numeric($longitude)) {
            return false;
        }

        $lon = (float) $longitude;
        if ($lon < -180.0 || $lon > 180.0) {
            return false;
        }

        // Radius must be numeric and non-negative
        if (!is_numeric($radius)) {
            return false;
        }

        $rad = (float) $radius;

        return $rad >= 0.0;
    }
}
