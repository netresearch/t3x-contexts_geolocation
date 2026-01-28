<?php

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Tests\Unit\Dto;

use Netresearch\ContextsGeolocation\Dto\GeoLocation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GeoLocation::class)]
final class GeoLocationTest extends TestCase
{
    #[Test]
    public function constructorSetsAllProperties(): void
    {
        $location = new GeoLocation(
            countryCode: 'DE',
            countryCode3: 'DEU',
            countryName: 'Germany',
            continentCode: 'EU',
            continentName: 'Europe',
            latitude: 51.3397,
            longitude: 12.3731,
            city: 'Leipzig',
            postalCode: '04109',
            region: 'SN',
            regionName: 'Saxony',
        );

        self::assertSame('DE', $location->countryCode);
        self::assertSame('DEU', $location->countryCode3);
        self::assertSame('Germany', $location->countryName);
        self::assertSame('EU', $location->continentCode);
        self::assertSame('Europe', $location->continentName);
        self::assertSame(51.3397, $location->latitude);
        self::assertSame(12.3731, $location->longitude);
        self::assertSame('Leipzig', $location->city);
        self::assertSame('04109', $location->postalCode);
        self::assertSame('SN', $location->region);
        self::assertSame('Saxony', $location->regionName);
    }

    #[Test]
    public function constructorDefaultsToNullValues(): void
    {
        $location = new GeoLocation();

        self::assertNull($location->countryCode);
        self::assertNull($location->countryCode3);
        self::assertNull($location->countryName);
        self::assertNull($location->continentCode);
        self::assertNull($location->continentName);
        self::assertNull($location->latitude);
        self::assertNull($location->longitude);
        self::assertNull($location->city);
        self::assertNull($location->postalCode);
        self::assertNull($location->region);
        self::assertNull($location->regionName);
    }

    #[Test]
    #[DataProvider('coordinatesDataProvider')]
    public function hasCoordinatesReturnsExpectedResult(
        ?float $latitude,
        ?float $longitude,
        bool $expected
    ): void {
        $location = new GeoLocation(
            latitude: $latitude,
            longitude: $longitude,
        );

        self::assertSame($expected, $location->hasCoordinates());
    }

    /**
     * @return iterable<string, array{?float, ?float, bool}>
     */
    public static function coordinatesDataProvider(): iterable
    {
        yield 'valid coordinates' => [51.3397, 12.3731, true];
        yield 'null latitude' => [null, 12.3731, false];
        yield 'null longitude' => [51.3397, null, false];
        yield 'both null' => [null, null, false];
        yield 'zero coordinates (null island)' => [0.0, 0.0, false];
        yield 'negative coordinates (valid)' => [-33.8688, 151.2093, true];
        yield 'zero latitude only (equator, valid)' => [0.0, 12.3731, true];
        yield 'zero longitude only (prime meridian, valid)' => [51.3397, 0.0, true];
    }
}
