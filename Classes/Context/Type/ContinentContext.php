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
 * Context type that matches based on visitor's continent.
 *
 * Matches when the visitor's continent code (detected via GeoIP) is in the
 * configured list of continent codes.
 *
 * Valid continent codes:
 * - AF: Africa
 * - AN: Antarctica
 * - AS: Asia
 * - EU: Europe
 * - NA: North America
 * - OC: Oceania
 * - SA: South America
 *
 * Configuration:
 * - field_continents: Comma-separated list of continent codes
 *
 * @author Netresearch DTT GmbH
 * @link https://www.netresearch.de
 */
class ContinentContext extends AbstractGeolocationContext
{
    /**
     * Valid continent codes.
     *
     * @var string[]
     */
    private const VALID_CONTINENT_CODES = ['AF', 'AN', 'AS', 'EU', 'NA', 'OC', 'SA'];

    /**
     * @param array<string, mixed> $arRow Database context row
     */
    public function __construct(array $arRow = [], ?GeoLocationService $geoLocationService = null)
    {
        parent::__construct($arRow, $geoLocationService);
    }

    /**
     * Get valid continent codes.
     *
     * @return array<int, string>
     */
    public static function getValidContinentCodes(): array
    {
        return self::VALID_CONTINENT_CODES;
    }

    /**
     * Check if the context matches the current request.
     *
     * @param array<int|string, mixed> $arDependencies Array of dependent context objects
     * @return bool True if the visitor's continent is in the configured list
     */
    public function match(array $arDependencies = []): bool
    {
        // Check session cache first
        [$bUseSession, $bMatch] = $this->getMatchFromSession();
        if ($bUseSession) {
            return $this->invert((bool) $bMatch);
        }

        // Get configured continents
        $configuredContinents = $this->parseCommaSeparatedList(
            $this->getConfValue('field_continents'),
        );

        if ($configuredContinents === []) {
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

        // Get continent code from GeoIP
        $service = $this->getGeoLocationService();
        if ($service === null) {
            return $this->storeInSession($this->invert(false));
        }

        $continentCode = $service->getLocationForIp($clientIp)?->continentCode;

        if ($continentCode === null) {
            return $this->storeInSession($this->invert(false));
        }

        // Check if visitor's continent is in the configured list
        $visitorContinent = strtoupper($continentCode);
        $bMatch = \in_array($visitorContinent, $configuredContinents, true);

        return $this->storeInSession($this->invert($bMatch));
    }
}
