<?php

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Tests\Unit\Service;

use Netresearch\ContextsGeolocation\Adapter\GeoIpAdapterInterface;
use Netresearch\ContextsGeolocation\Dto\GeoLocation;
use Netresearch\ContextsGeolocation\Service\GeoLocationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

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
        yield 'Public IPv6' => ['2001:db8::1'];
    }

    #[Test]
    public function getLocationForIpReturnsLocationFromAdapter(): void
    {
        $expectedLocation = new GeoLocation(countryCode: 'DE');
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn($expectedLocation);

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
    public function getClientIpAddressReturnsRemoteAddrByDefault(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $service = new GeoLocationService($adapter, trustProxyHeaders: false);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);

        $result = $service->getClientIpAddress($request);

        self::assertSame('8.8.8.8', $result);
    }

    #[Test]
    public function getClientIpAddressReturnsNullWhenRemoteAddrMissing(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $service = new GeoLocationService($adapter, trustProxyHeaders: false);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn([]);

        $result = $service->getClientIpAddress($request);

        self::assertNull($result);
    }

    #[Test]
    public function getClientIpAddressIgnoresProxyHeadersByDefault(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $service = new GeoLocationService($adapter, trustProxyHeaders: false);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')
            ->willReturnMap([
                ['X-Forwarded-For', '1.2.3.4'],
            ]);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);

        $result = $service->getClientIpAddress($request);

        self::assertSame('8.8.8.8', $result);
    }

    #[Test]
    public function getClientIpAddressUsesXForwardedForWhenTrusted(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $service = new GeoLocationService($adapter, trustProxyHeaders: true);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')
            ->willReturnCallback(function (string $header): string {
                return match ($header) {
                    'X-Forwarded-For' => '1.2.3.4, 5.6.7.8',
                    default => '',
                };
            });
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);

        $result = $service->getClientIpAddress($request);

        // Should use first IP from X-Forwarded-For
        self::assertSame('1.2.3.4', $result);
    }

    #[Test]
    public function getClientIpAddressUsesXRealIpWhenXForwardedForMissing(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $service = new GeoLocationService($adapter, trustProxyHeaders: true);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')
            ->willReturnCallback(function (string $header): string {
                return match ($header) {
                    'X-Forwarded-For' => '',
                    'X-Real-IP' => '9.9.9.9',
                    default => '',
                };
            });
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);

        $result = $service->getClientIpAddress($request);

        self::assertSame('9.9.9.9', $result);
    }

    #[Test]
    public function getClientIpAddressFallsBackToRemoteAddrWhenProxyHeadersInvalid(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $service = new GeoLocationService($adapter, trustProxyHeaders: true);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')
            ->willReturnCallback(function (string $header): string {
                return match ($header) {
                    'X-Forwarded-For' => 'invalid-ip',
                    'X-Real-IP' => 'also-invalid',
                    default => '',
                };
            });
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);

        $result = $service->getClientIpAddress($request);

        self::assertSame('8.8.8.8', $result);
    }

    #[Test]
    public function getClientIpAddressSupportsCustomProxyHeaders(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $service = new GeoLocationService(
            $adapter,
            trustProxyHeaders: true,
            proxyHeaders: ['CF-Connecting-IP', 'True-Client-IP'],
        );

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')
            ->willReturnCallback(function (string $header): string {
                return match ($header) {
                    'CF-Connecting-IP' => '1.1.1.1',
                    default => '',
                };
            });
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);

        $result = $service->getClientIpAddress($request);

        self::assertSame('1.1.1.1', $result);
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
            $adapter->method('lookup')->with('8.8.8.8')->willReturn($expectedLocation);

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
}
