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
 * Context type that matches based on visitor's country.
 *
 * Matches when the visitor's country code (detected via GeoIP) is in the
 * configured list of country codes. Country codes are ISO 3166-1 alpha-2
 * format (e.g., DE, US, FR).
 *
 * Configuration:
 * - field_countries: Comma-separated list of country codes
 *
 * @author Netresearch DTT GmbH
 * @link https://www.netresearch.de
 */
class CountryContext extends AbstractGeolocationContext
{
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
     * @return bool True if the visitor's country is in the configured list
     */
    public function match(array $arDependencies = []): bool
    {
        // Check session cache first
        [$bUseSession, $bMatch] = $this->getMatchFromSession();
        if ($bUseSession) {
            return $this->invert((bool) $bMatch);
        }

        // Get configured countries
        $configuredCountries = $this->parseCommaSeparatedList(
            $this->getConfValue('field_countries'),
        );

        if ($configuredCountries === []) {
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

        // Get country code from GeoIP
        $service = $this->getGeoLocationService();
        if ($service === null) {
            return $this->storeInSession($this->invert(false));
        }

        $countryCode = $service->getLocationForIp($clientIp)?->countryCode;

        if ($countryCode === null) {
            return $this->storeInSession($this->invert(false));
        }

        // Check if visitor's country is in the configured list
        $visitorCountry = strtoupper($countryCode);
        $bMatch = \in_array($visitorCountry, $configuredCountries, true);

        return $this->storeInSession($this->invert($bMatch));
    }
}
