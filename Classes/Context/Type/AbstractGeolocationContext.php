<?php

/**
 * This file is part of the package netresearch/contexts-geolocation.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Context\Type;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\ContextsGeolocation\Service\GeoLocationService;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Abstract base class for geolocation context types.
 *
 * Provides common functionality for geolocation contexts including
 * IP address extraction from requests and integration with the
 * GeoLocationService.
 */
abstract class AbstractGeolocationContext extends AbstractContext
{
    protected GeoLocationService $geoLocationService;

    /**
     * @param array<string, mixed> $arRow Database context row
     */
    public function __construct(array $arRow = [], ?GeoLocationService $geoLocationService = null)
    {
        parent::__construct($arRow);

        if ($geoLocationService !== null) {
            $this->geoLocationService = $geoLocationService;
        }
    }

    /**
     * Get the current HTTP request.
     */
    protected function getRequest(): ?ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? null;
    }

    /**
     * Get the client IP address from the current request.
     *
     * @return string|null Client IP address or null if not available
     */
    protected function getClientIpAddress(): ?string
    {
        $request = $this->getRequest();

        if ($request === null) {
            return null;
        }

        return $this->geoLocationService->getClientIpAddress($request);
    }

    /**
     * Check if the given IP is a private/reserved address.
     */
    protected function isPrivateIp(string $ip): bool
    {
        return $this->geoLocationService->isPrivateIp($ip);
    }

    /**
     * Parse a comma-separated list of values into a normalized array.
     *
     * @param string $value Comma-separated string
     * @return array<int, string> Array of trimmed, uppercased values
     */
    protected function parseCommaSeparatedList(string $value): array
    {
        if ($value === '') {
            return [];
        }

        return array_filter(
            array_map(
                static fn(string $item): string => strtoupper(trim($item)),
                explode(',', $value),
            ),
            static fn(string $item): bool => $item !== '',
        );
    }
}
