<?php

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Service;

use Netresearch\ContextsGeolocation\Adapter\GeoIpAdapterInterface;
use Netresearch\ContextsGeolocation\Dto\GeoLocation;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Service for geolocation lookups.
 *
 * Provides geolocation data for IP addresses using the configured GeoIP adapter.
 * Handles client IP detection from HTTP requests including proxy headers.
 */
final class GeoLocationService
{
    /**
     * @param GeoIpAdapterInterface $adapter The GeoIP adapter to use for lookups
     * @param bool $trustProxyHeaders Whether to trust X-Forwarded-For and similar headers
     * @param array<string> $proxyHeaders List of proxy headers to check (in order of priority)
     */
    public function __construct(
        private readonly GeoIpAdapterInterface $adapter,
        private readonly bool $trustProxyHeaders = false,
        private readonly array $proxyHeaders = ['X-Forwarded-For', 'X-Real-IP'],
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

        if ($ipAddress === null || $this->isPrivateIp($ipAddress)) {
            return null;
        }

        return $this->adapter->lookup($ipAddress);
    }

    /**
     * Get geolocation for a specific IP address.
     *
     * @param string $ipAddress IPv4 or IPv6 address
     */
    public function getLocationForIp(string $ipAddress): ?GeoLocation
    {
        if ($this->isPrivateIp($ipAddress)) {
            return null;
        }

        return $this->adapter->lookup($ipAddress);
    }

    /**
     * Get the client IP address from a PSR-7 request.
     *
     * Handles X-Forwarded-For and similar proxy headers when configured.
     */
    public function getClientIpAddress(ServerRequestInterface $request): ?string
    {
        if ($this->trustProxyHeaders) {
            foreach ($this->proxyHeaders as $header) {
                $value = $request->getHeaderLine($header);
                if ($value !== '') {
                    // X-Forwarded-For may contain multiple IPs: "client, proxy1, proxy2"
                    $ips = array_map('trim', explode(',', $value));
                    $clientIp = $ips[0];
                    if ($this->isValidIpAddress($clientIp)) {
                        return $clientIp;
                    }
                }
            }
        }

        $serverParams = $request->getServerParams();
        $remoteAddr = isset($serverParams['REMOTE_ADDR'])
            ? (string) $serverParams['REMOTE_ADDR']
            : null;

        if ($remoteAddr !== null && $this->isValidIpAddress($remoteAddr)) {
            return $remoteAddr;
        }

        return null;
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
