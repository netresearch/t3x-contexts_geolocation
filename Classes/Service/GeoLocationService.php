<?php

/*
 * Copyright (c) 2025-2026 Netresearch DTT GmbH
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Service;

use Netresearch\ContextsGeolocation\Adapter\GeoIpAdapterInterface;
use Netresearch\ContextsGeolocation\Dto\GeoLocation;
use Netresearch\ContextsGeolocation\Exception\GeoIpException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;

/**
 * Service for geolocation lookups.
 *
 * Provides geolocation data for IP addresses using the configured GeoIP adapter.
 * The client IP address is the one TYPO3 determines for the request, which
 * honours the reverse proxy configuration in
 * $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'].
 */
final readonly class GeoLocationService
{
    /**
     * @param GeoIpAdapterInterface $adapter The GeoIP adapter to use for lookups
     * @param LoggerInterface|null $logger Receives failed lookups (missing or unreadable database)
     */
    public function __construct(
        private GeoIpAdapterInterface $adapter,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Get geolocation for the current TYPO3 request.
     *
     * Uses the global TYPO3_REQUEST if available.
     */
    public function getLocationForRequest(): ?GeoLocation
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$request instanceof ServerRequestInterface) {
            return null;
        }

        $ipAddress = $this->getClientIpAddress($request);

        if ($ipAddress === null) {
            return null;
        }

        return $this->getLocationForIp($ipAddress);
    }

    /**
     * Get geolocation for a specific IP address.
     *
     * Returns null when the address is private or reserved, unknown to the
     * database, or when the database cannot be used; the last case is logged.
     *
     * @param string $ipAddress IPv4 or IPv6 address
     */
    public function getLocationForIp(string $ipAddress): ?GeoLocation
    {
        if ($this->isPrivateIp($ipAddress)) {
            return null;
        }

        try {
            return $this->adapter->lookup($ipAddress);
        } catch (GeoIpException $e) {
            $this->logger?->warning('GeoIP lookup failed: {message}', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Get the client IP address from a PSR-7 request.
     *
     * Uses the "normalizedParams" request attribute, which TYPO3 sets for
     * every frontend and backend request. It takes the address from
     * X-Forwarded-For only when REMOTE_ADDR is a proxy listed in
     * $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP']. Without the
     * attribute, REMOTE_ADDR is used.
     */
    public function getClientIpAddress(ServerRequestInterface $request): ?string
    {
        $normalizedParams = $request->getAttribute('normalizedParams');
        if ($normalizedParams instanceof NormalizedParams) {
            $remoteAddr = $normalizedParams->getRemoteAddress();
        } else {
            $serverParams = $request->getServerParams();
            $remoteAddr = isset($serverParams['REMOTE_ADDR']) ? (string) $serverParams['REMOTE_ADDR'] : '';
        }

        return $this->isValidIpAddress($remoteAddr) ? $remoteAddr : null;
    }

    /**
     * Check if an IP address is in a private or reserved range.
     *
     * Private ranges (return true):
     * - IPv4: 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16
     * - IPv6: fc00::/7 (unique local)
     * - Loopback: 127.0.0.0/8, ::1
     * - Link-local: 169.254.0.0/16, fe80::/10
     */
    public function isPrivateIp(string $ip): bool
    {
        // FILTER_FLAG_NO_PRIV_RANGE excludes private ranges
        // FILTER_FLAG_NO_RES_RANGE excludes reserved ranges (loopback, link-local, etc.)
        $result = filter_var(
            $ip,
            \FILTER_VALIDATE_IP,
            \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE,
        );

        // If filter returns false, the IP is either invalid or private/reserved
        return $result === false;
    }

    /**
     * Check if the underlying adapter is available.
     */
    public function isAvailable(): bool
    {
        return $this->adapter->isAvailable();
    }

    /**
     * Check if a string is a valid IP address (IPv4 or IPv6).
     */
    private function isValidIpAddress(string $ip): bool
    {
        return filter_var($ip, \FILTER_VALIDATE_IP) !== false;
    }
}
