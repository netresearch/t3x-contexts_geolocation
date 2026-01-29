<?php

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Tests\Unit\Adapter;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\Model\City;
use Netresearch\ContextsGeolocation\Adapter\GeoIpAdapterInterface;
use Netresearch\ContextsGeolocation\Adapter\MaxMindGeoIp2Adapter;
use Netresearch\ContextsGeolocation\Dto\GeoLocation;
use Netresearch\ContextsGeolocation\Exception\GeoIpException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(MaxMindGeoIp2Adapter::class)]
final class MaxMindGeoIp2AdapterTest extends TestCase
{
    private ?string $tempFilePath = null;

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up any temp file created during the test
        if ($this->tempFilePath !== null && file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
            $this->tempFilePath = null;
        }
    }

    #[Test]
    public function implementsGeoIpAdapterInterface(): void
    {
        $adapter = new MaxMindGeoIp2Adapter('/path/to/GeoLite2-City.mmdb');

        self::assertInstanceOf(GeoIpAdapterInterface::class, $adapter);
    }

    #[Test]
    public function isAvailableReturnsFalseWhenDatabaseDoesNotExist(): void
    {
        $adapter = new MaxMindGeoIp2Adapter('/nonexistent/path/to/database.mmdb');

        self::assertFalse($adapter->isAvailable());
    }

    #[Test]
    public function isAvailableReturnsTrueWhenDatabaseExists(): void
    {
        // Create a temporary file to simulate database
        $tempFile = sys_get_temp_dir() . '/test-geoip-' . uniqid() . '.mmdb';
        file_put_contents($tempFile, 'test content');

        try {
            $adapter = new MaxMindGeoIp2Adapter($tempFile);
            self::assertTrue($adapter->isAvailable());
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function lookupThrowsExceptionWhenDatabaseNotAvailable(): void
    {
        $adapter = new MaxMindGeoIp2Adapter('/nonexistent/path/to/database.mmdb');

        $this->expectException(GeoIpException::class);
        $this->expectExceptionMessage('GeoIP2 database not available');

        $adapter->lookup('8.8.8.8');
    }

    #[Test]
    public function lookupReturnsGeoLocationOnSuccess(): void
    {
        $adapter = $this->createAdapterWithMockedReader(
            $this->createMockCityRecord([
                'country_code' => 'US',
                'country_name' => 'United States',
                'continent_code' => 'NA',
                'continent_name' => 'North America',
                'latitude' => 37.751,
                'longitude' => -97.822,
                'city' => 'Mountain View',
                'postal_code' => '94035',
                'region_code' => 'CA',
                'region_name' => 'California',
            ]),
        );

        $result = $adapter->lookup('8.8.8.8');

        self::assertInstanceOf(GeoLocation::class, $result);
        self::assertSame('US', $result->countryCode);
        self::assertSame('United States', $result->countryName);
        self::assertSame('NA', $result->continentCode);
        self::assertSame('North America', $result->continentName);
        self::assertSame(37.751, $result->latitude);
        self::assertSame(-97.822, $result->longitude);
        self::assertSame('Mountain View', $result->city);
        self::assertSame('94035', $result->postalCode);
        self::assertSame('CA', $result->region);
        self::assertSame('California', $result->regionName);
        self::assertNull($result->countryCode3); // GeoIP2 doesn't provide alpha-3 codes
    }

    #[Test]
    public function lookupReturnsNullWhenAddressNotFound(): void
    {
        $reader = $this->createMock(Reader::class);
        $reader->method('city')->willThrowException(new AddressNotFoundException('Address not found'));

        $adapter = $this->createAdapterWithReader($reader);

        $result = $adapter->lookup('0.0.0.0');

        self::assertNull($result);
    }

    #[Test]
    public function getCountryCodeReturnsCodeOnSuccess(): void
    {
        $adapter = $this->createAdapterWithMockedReader(
            $this->createMockCityRecord(['country_code' => 'DE']),
        );

        $result = $adapter->getCountryCode('8.8.8.8');

        self::assertSame('DE', $result);
    }

    #[Test]
    public function getCountryCodeReturnsNullWhenAddressNotFound(): void
    {
        $reader = $this->createMock(Reader::class);
        $reader->method('city')->willThrowException(new AddressNotFoundException('Address not found'));

        $adapter = $this->createAdapterWithReader($reader);

        self::assertNull($adapter->getCountryCode('0.0.0.0'));
    }

    #[Test]
    public function getCountryNameReturnsNameOnSuccess(): void
    {
        $adapter = $this->createAdapterWithMockedReader(
            $this->createMockCityRecord(['country_name' => 'Germany']),
        );

        $result = $adapter->getCountryName('8.8.8.8');

        self::assertSame('Germany', $result);
    }

    #[Test]
    public function getContinentCodeReturnsCodeOnSuccess(): void
    {
        $adapter = $this->createAdapterWithMockedReader(
            $this->createMockCityRecord(['continent_code' => 'EU']),
        );

        $result = $adapter->getContinentCode('8.8.8.8');

        self::assertSame('EU', $result);
    }

    #[Test]
    public function getLatitudeReturnsLatitudeOnSuccess(): void
    {
        $adapter = $this->createAdapterWithMockedReader(
            $this->createMockCityRecord(['latitude' => 51.3397]),
        );

        $result = $adapter->getLatitude('8.8.8.8');

        self::assertSame(51.3397, $result);
    }

    #[Test]
    public function getLongitudeReturnsLongitudeOnSuccess(): void
    {
        $adapter = $this->createAdapterWithMockedReader(
            $this->createMockCityRecord(['longitude' => 12.3731]),
        );

        $result = $adapter->getLongitude('8.8.8.8');

        self::assertSame(12.3731, $result);
    }

    #[Test]
    public function getCityReturnsCityOnSuccess(): void
    {
        $adapter = $this->createAdapterWithMockedReader(
            $this->createMockCityRecord(['city' => 'Leipzig']),
        );

        $result = $adapter->getCity('8.8.8.8');

        self::assertSame('Leipzig', $result);
    }

    #[Test]
    public function getCityReturnsNullWhenAddressNotFound(): void
    {
        $reader = $this->createMock(Reader::class);
        $reader->method('city')->willThrowException(new AddressNotFoundException('Address not found'));

        $adapter = $this->createAdapterWithReader($reader);

        self::assertNull($adapter->getCity('0.0.0.0'));
    }

    /**
     * Create a mock City record with the given data.
     *
     * @param array<string, mixed> $data
     */
    private function createMockCityRecord(array $data): City
    {
        $rawData = [
            'country' => [
                'iso_code' => $data['country_code'] ?? null,
                'names' => ['en' => $data['country_name'] ?? null],
            ],
            'continent' => [
                'code' => $data['continent_code'] ?? null,
                'names' => ['en' => $data['continent_name'] ?? null],
            ],
            'location' => [
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ],
            'city' => [
                'names' => ['en' => $data['city'] ?? null],
            ],
            'postal' => [
                'code' => $data['postal_code'] ?? null,
            ],
            'subdivisions' => [
                [
                    'iso_code' => $data['region_code'] ?? null,
                    'names' => ['en' => $data['region_name'] ?? null],
                ],
            ],
        ];

        return new City($rawData, ['en']);
    }

    /**
     * Create an adapter with a mocked Reader that returns the given City record.
     */
    private function createAdapterWithMockedReader(City $cityRecord): MaxMindGeoIp2Adapter
    {
        $reader = $this->createMock(Reader::class);
        $reader->method('city')->willReturn($cityRecord);

        return $this->createAdapterWithReader($reader);
    }

    /**
     * Create an adapter with a specific Reader instance injected.
     *
     * Uses reflection to inject the Reader directly to bypass file system checks.
     * The temp file is cleaned up in tearDown().
     */
    private function createAdapterWithReader(Reader $reader): MaxMindGeoIp2Adapter
    {
        // Create a temporary file so isAvailable() returns true
        $this->tempFilePath = sys_get_temp_dir() . '/test-geoip-' . uniqid() . '.mmdb';
        file_put_contents($this->tempFilePath, 'test content');

        $adapter = new MaxMindGeoIp2Adapter($this->tempFilePath);

        // Use reflection to inject the mocked reader
        $reflection = new ReflectionClass($adapter);
        $property = $reflection->getProperty('reader');
        $property->setValue($adapter, $reader);

        return $adapter;
    }
}
