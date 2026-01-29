<?php

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Tests\Unit\Context\Type;

use Netresearch\ContextsGeolocation\Adapter\GeoIpAdapterInterface;
use Netresearch\ContextsGeolocation\Context\Type\DistanceContext;
use Netresearch\ContextsGeolocation\Dto\GeoLocation;
use Netresearch\ContextsGeolocation\Service\GeoLocationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(DistanceContext::class)]
final class DistanceContextTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
    }

    /**
     * @return iterable<string, array{float, float, float, float, float, float}>
     */
    public static function distanceCalculationDataProvider(): iterable
    {
        // Known distances for testing Haversine formula accuracy
        // Format: [lat1, lon1, lat2, lon2, expected_distance_km, tolerance_km]

        // Leipzig to Berlin: ~153 km
        yield 'Leipzig to Berlin' => [51.3397, 12.3731, 52.5200, 13.4050, 153.0, 5.0];

        // Same point (distance = 0)
        yield 'Same point' => [51.3397, 12.3731, 51.3397, 12.3731, 0.0, 1.0];

        // London to Paris: ~344 km
        yield 'London to Paris' => [51.5074, -0.1278, 48.8566, 2.3522, 344.0, 10.0];

        // New York to Los Angeles: ~3936 km
        yield 'New York to Los Angeles' => [40.7128, -74.0060, 34.0522, -118.2437, 3936.0, 50.0];
    }

    #[Test]
    public function matchReturnsTrueWhenWithinRadius(): void
    {
        // Leipzig coordinates: 51.3397 N, 12.3731 E
        // Berlin coordinates: 52.5200 N, 13.4050 E
        // Distance: ~153 km

        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(latitude: 52.5200, longitude: 13.4050),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableDistanceContext(
            '51.3397',
            '12.3731',
            '200', // 200 km - should include Berlin
            $service,
        );

        self::assertTrue($context->match());
    }

    #[Test]
    public function matchReturnsFalseWhenOutsideRadius(): void
    {
        // Leipzig to Berlin is ~153 km
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(latitude: 52.5200, longitude: 13.4050),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableDistanceContext(
            '51.3397',
            '12.3731',
            '100', // 100 km - Berlin is too far
            $service,
        );

        self::assertFalse($context->match());
    }

    #[Test]
    public function matchReturnsFalseWhenCoordinatesNotAvailable(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(latitude: null, longitude: null),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableDistanceContext(
            '51.3397',
            '12.3731',
            '200',
            $service,
        );

        self::assertFalse($context->match());
    }

    #[Test]
    public function matchReturnsInvertedResultWhenInvertIsTrue(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(latitude: 52.5200, longitude: 13.4050),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableDistanceContext(
            '51.3397',
            '12.3731',
            '200',
            $service,
            invert: true,
        );

        // Within radius, but inverted should return false
        self::assertFalse($context->match());
    }

    #[Test]
    public function matchReturnsFalseForPrivateIp(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->expects(self::never())->method('lookup');

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '172.16.0.1']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableDistanceContext(
            '51.3397',
            '12.3731',
            '200',
            $service,
        );

        self::assertFalse($context->match());
    }

    #[Test]
    public function matchReturnsFalseWhenNoRequestAvailable(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $service = new GeoLocationService($adapter);

        // No TYPO3_REQUEST set
        $context = $this->createTestableDistanceContext(
            '51.3397',
            '12.3731',
            '200',
            $service,
        );

        self::assertFalse($context->match());
    }

    #[Test]
    public function matchReturnsFalseWhenConfigurationIncomplete(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(latitude: 52.5200, longitude: 13.4050),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        // Missing radius
        $context = $this->createTestableDistanceContext(
            '51.3397',
            '12.3731',
            '',
            $service,
        );

        self::assertFalse($context->match());
    }

    #[Test]
    public function matchReturnsFalseWhenLookupReturnsNull(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(null);

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableDistanceContext(
            '51.3397',
            '12.3731',
            '200',
            $service,
        );

        self::assertFalse($context->match());
    }

    #[Test]
    #[DataProvider('distanceCalculationDataProvider')]
    public function haversineDistanceCalculationIsAccurate(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2,
        float $expectedDistance,
        float $tolerance,
    ): void {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(latitude: $lat2, longitude: $lon2),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        // Test with radius slightly larger than expected distance (should match)
        $context = $this->createTestableDistanceContext(
            (string) $lat1,
            (string) $lon1,
            (string) ($expectedDistance + $tolerance),
            $service,
        );

        self::assertTrue($context->match(), "Should match with radius {$expectedDistance} + {$tolerance}km");

        // Test with radius slightly smaller than expected distance (should not match)
        $context2 = $this->createTestableDistanceContext(
            (string) $lat1,
            (string) $lon1,
            (string) ($expectedDistance - $tolerance),
            $service,
        );

        self::assertFalse($context2->match(), "Should not match with radius {$expectedDistance} - {$tolerance}km");
    }

    #[Test]
    public function matchHandlesZeroRadius(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(latitude: 51.3397, longitude: 12.3731),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        // Zero radius - only exact match
        $context = $this->createTestableDistanceContext(
            '51.3397',
            '12.3731',
            '0',
            $service,
        );

        // Same coordinates should still match with 0 radius
        self::assertTrue($context->match());
    }

    #[Test]
    public function matchHandlesNegativeLatitude(): void
    {
        // Sydney, Australia: -33.8688 S, 151.2093 E
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(latitude: -33.8688, longitude: 151.2093),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableDistanceContext(
            '-33.8688',
            '151.2093',
            '10',
            $service,
        );

        self::assertTrue($context->match());
    }

    #[Test]
    public function matchHandlesNegativeLongitude(): void
    {
        // New York: 40.7128 N, -74.0060 W
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(latitude: 40.7128, longitude: -74.0060),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableDistanceContext(
            '40.7128',
            '-74.0060',
            '10',
            $service,
        );

        self::assertTrue($context->match());
    }

    #[Test]
    public function calculateHaversineDistanceReturnsCorrectDistance(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $service = new GeoLocationService($adapter);

        $context = $this->createTestableDistanceContext(
            '0',
            '0',
            '100',
            $service,
        );

        // Leipzig to Berlin: should be around 153 km
        $distance = $context->calculateHaversineDistance(
            51.3397,
            12.3731,
            52.5200,
            13.4050,
        );

        // Allow 5km tolerance for rounding differences
        self::assertEqualsWithDelta(153.0, $distance, 5.0);
    }

    #[Test]
    public function calculateHaversineDistanceReturnsZeroForSamePoint(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $service = new GeoLocationService($adapter);

        $context = $this->createTestableDistanceContext(
            '0',
            '0',
            '100',
            $service,
        );

        $distance = $context->calculateHaversineDistance(
            51.3397,
            12.3731,
            51.3397,
            12.3731,
        );

        self::assertSame(0.0, $distance);
    }

    /**
     * Create a testable DistanceContext that bypasses TYPO3 dependencies.
     */
    private function createTestableDistanceContext(
        string $latitude,
        string $longitude,
        string $radius,
        GeoLocationService $service,
        bool $invert = false,
    ): DistanceContext {
        return new class ($latitude, $longitude, $radius, $service, $invert) extends DistanceContext {
            private string $testLatitude;

            private string $testLongitude;

            private string $testRadius;

            private bool $testInvert;

            public function __construct(
                string $latitude,
                string $longitude,
                string $radius,
                GeoLocationService $service,
                bool $invert,
            ) {
                // Skip parent constructor to avoid TYPO3 dependencies
                $this->testLatitude = $latitude;
                $this->testLongitude = $longitude;
                $this->testRadius = $radius;
                $this->testInvert = $invert;
                $this->geoLocationService = $service;
                $this->use_session = false;
            }

            protected function getConfValue(
                string $fieldName,
                string $default = '',
                string $sheet = 'sDEF',
                string $lang = 'lDEF',
                string $value = 'vDEF',
            ): string {
                return match ($fieldName) {
                    'field_latitude' => $this->testLatitude,
                    'field_longitude' => $this->testLongitude,
                    'field_radius' => $this->testRadius,
                    default => $default,
                };
            }

            protected function invert(bool $bMatch): bool
            {
                if ($this->testInvert) {
                    return !$bMatch;
                }

                return $bMatch;
            }

            protected function getMatchFromSession(): array
            {
                return [false, null];
            }

            protected function storeInSession(bool $bMatch): bool
            {
                return $bMatch;
            }
        };
    }
}
