<?php

/*
 * Copyright (c) 2025-2026 Netresearch DTT GmbH
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Tests\Unit\Service;

use Netresearch\ContextsGeolocation\Adapter\GeoIpAdapterInterface;
use Netresearch\ContextsGeolocation\Dto\GeoLocation;
use Netresearch\ContextsGeolocation\Exception\GeoIpException;
use Netresearch\ContextsGeolocation\Service\GeoLocationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;

#[CoversClass(GeoLocationService::class)]
final class GeoLocationServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function privateIpAddressesDataProvider(): iterable
    {
        // IPv4 private ranges
        yield '10.0.0.0/8' => ['10.0.0.1'];
        yield '10.255.255.255' => ['10.255.255.255'];
        yield '172.16.0.0/12' => ['172.16.0.1'];
        yield '172.31.255.255' => ['172.31.255.255'];
        yield '192.168.0.0/16' => ['192.168.0.1'];
        yield '192.168.255.255' => ['192.168.255.255'];

        // Loopback
        yield 'IPv4 loopback' => ['127.0.0.1'];
        yield 'IPv6 loopback' => ['::1'];

        // Link-local
        yield 'IPv4 link-local' => ['169.254.1.1'];
        yield 'IPv6 link-local' => ['fe80::1'];

        // IPv6 unique local (fc00::/7)
        yield 'IPv6 unique local' => ['fd00::1'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function publicIpAddressesDataProvider(): iterable
    {
        yield 'Google DNS' => ['8.8.8.8'];
        yield 'Cloudflare DNS' => ['1.1.1.1'];
        yield 'Public IPv4' => ['203.0.113.50'];
        yield 'Public IPv6' => ['2001:4860:4860::8888']; // Google Public DNS
    }

    #[Test]
    public function getLocationForIpReturnsLocationFromAdapter(): void
    {
        $expectedLocation = new GeoLocation(countryCode: 'DE');
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->expects(self::atLeastOnce())->method('lookup')->with('8.8.8.8')->willReturn($expectedLocation);

        $service = new GeoLocationService($adapter);
        $result = $service->getLocationForIp('8.8.8.8');

        self::assertSame($expectedLocation, $result);
    }

    #[Test]
    public function getLocationForIpReturnsNullForPrivateIp(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->expects(self::never())->method('lookup');

        $service = new GeoLocationService($adapter);
        $result = $service->getLocationForIp('192.168.1.1');

        self::assertNull($result);
    }

    #[Test]
    #[DataProvider('privateIpAddressesDataProvider')]
    public function isPrivateIpReturnsTrueForPrivateAddresses(string $ip): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $service = new GeoLocationService($adapter);

        self::assertTrue($service->isPrivateIp($ip));
    }

    #[Test]
    #[DataProvider('publicIpAddressesDataProvider')]
    public function isPrivateIpReturnsFalseForPublicAddresses(string $ip): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $service = new GeoLocationService($adapter);

        self::assertFalse($service->isPrivateIp($ip));
    }

    #[Test]
    public function getClientIpAddressReturnsRemoteAddrWithoutNormalizedParams(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);

        self::assertSame('8.8.8.8', $service->getClientIpAddress($request));
    }

    #[Test]
    public function getClientIpAddressReturnsNullWhenRemoteAddrMissing(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn([]);

        self::assertNull($service->getClientIpAddress($request));
    }

    #[Test]
    public function getClientIpAddressIgnoresForwardedForFromAClientThatIsNoConfiguredProxy(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $service = new GeoLocationService($adapter);

        $request = $this->createRequestWithNormalizedParams(['reverseProxyIP' => '']);

        self::assertSame('203.0.113.10', $service->getClientIpAddress($request));
    }

    #[Test]
    public function getClientIpAddressUsesForwardedForFromAConfiguredReverseProxy(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $service = new GeoLocationService($adapter);

        $request = $this->createRequestWithNormalizedParams([
            'reverseProxyIP' => '203.0.113.10',
            'reverseProxyHeaderMultiValue' => 'first',
        ]);

        self::assertSame('198.51.100.7', $service->getClientIpAddress($request));
    }

    #[Test]
    public function getLocationForIpReturnsNullAndLogsWhenTheDatabaseCannotBeUsed(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->willThrowException(
            new GeoIpException('GeoIP2 database not available: check GEOIP_DATABASE_PATH', 2024837309),
        );
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $service = new GeoLocationService($adapter, $logger);

        self::assertNull($service->getLocationForIp('8.8.8.8'));
    }

    #[Test]
    public function isAvailableDelegatesToAdapter(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('isAvailable')->willReturn(true);

        $service = new GeoLocationService($adapter);

        self::assertTrue($service->isAvailable());
    }

    #[Test]
    public function getLocationForRequestReturnsNullWhenNoGlobalRequest(): void
    {
        // Ensure TYPO3_REQUEST is not set
        unset($GLOBALS['TYPO3_REQUEST']);

        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->expects(self::never())->method('lookup');

        $service = new GeoLocationService($adapter);
        $result = $service->getLocationForRequest();

        self::assertNull($result);
    }

    #[Test]
    public function getLocationForRequestUsesGlobalTYPO3Request(): void
    {
        $expectedLocation = new GeoLocation(countryCode: 'DE');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');

        $GLOBALS['TYPO3_REQUEST'] = $request;

        try {
            $adapter = $this->createMock(GeoIpAdapterInterface::class);
            $adapter->expects(self::atLeastOnce())->method('lookup')->with('8.8.8.8')->willReturn($expectedLocation);

            $service = new GeoLocationService($adapter);
            $result = $service->getLocationForRequest();

            self::assertSame($expectedLocation, $result);
        } finally {
            unset($GLOBALS['TYPO3_REQUEST']);
        }
    }

    #[Test]
    public function getLocationForRequestReturnsNullForPrivateIp(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '192.168.1.1']);
        $request->method('getHeaderLine')->willReturn('');

        $GLOBALS['TYPO3_REQUEST'] = $request;

        try {
            $adapter = $this->createMock(GeoIpAdapterInterface::class);
            $adapter->expects(self::never())->method('lookup');

            $service = new GeoLocationService($adapter);
            $result = $service->getLocationForRequest();

            self::assertNull($result);
        } finally {
            unset($GLOBALS['TYPO3_REQUEST']);
        }
    }

    /**
     * Request carrying the "normalizedParams" attribute TYPO3 sets, for a
     * client 198.51.100.7 behind 203.0.113.10.
     *
     * @param array<string, string> $systemConfiguration $GLOBALS['TYPO3_CONF_VARS']['SYS'] subset
     */
    private function createRequestWithNormalizedParams(array $systemConfiguration): ServerRequestInterface
    {
        $serverParams = [
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.7',
        ];
        $normalizedParams = new NormalizedParams($serverParams, $systemConfiguration, '', '');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn($serverParams);
        $request->method('getAttribute')->willReturnCallback(
            static fn(string $name): ?NormalizedParams => $name === 'normalizedParams' ? $normalizedParams : null,
        );

        return $request;
    }
}
