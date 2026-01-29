<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-01-28 -->

# AGENTS.md - Classes/

Backend PHP code for the Contexts Geolocation extension.

## Overview

This directory contains the core PHP implementation:
- **Adapter/**: GeoIP adapter implementations (MaxMind GeoIP2)
- **Context/Type/**: Geolocation context types (Country, Continent, Distance)
- **Service/**: GeoIP lookup service
- **Dto/**: Value objects for geolocation data
- **Exception/**: Custom exceptions

## Setup & Environment

```bash
composer install
ddev start && ddev install-v13
```

## Build & Tests

```bash
composer lint              # PHP_CodeSniffer
composer analyze           # PHPStan level 10
composer test:unit         # Unit tests for this code
```

## Code Style & Conventions

### PSR-12 + TYPO3 CGL

- Strict types: `declare(strict_types=1);`
- Final classes by default (unless designed for extension)
- Constructor property promotion where applicable
- Return types on all methods

### Namespace Pattern

```php
namespace Netresearch\ContextsGeolocation\Adapter;
namespace Netresearch\ContextsGeolocation\Context\Type;
namespace Netresearch\ContextsGeolocation\Service;
namespace Netresearch\ContextsGeolocation\Dto;
```

### Dependency Injection

Prefer constructor injection via `Services.yaml`:

```php
public function __construct(
    private readonly GeoIpService $geoIpService,
) {}
```

## Extension-Specific Patterns

### GeoIP Adapter Interface

```php
<?php
declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Adapter;

use Netresearch\ContextsGeolocation\Dto\GeoLocation;

interface GeoIpAdapterInterface
{
    /**
     * Look up geolocation data for an IP address.
     *
     * @param string $ipAddress IPv4 or IPv6 address
     * @return GeoLocation|null Null if IP cannot be resolved
     */
    public function lookup(string $ipAddress): ?GeoLocation;

    /**
     * Check if the adapter is available and configured.
     */
    public function isAvailable(): bool;
}
```

### MaxMind GeoIP2 Adapter

```php
<?php
declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Adapter;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Netresearch\ContextsGeolocation\Dto\GeoLocation;
use Netresearch\ContextsGeolocation\Exception\GeoIpException;

final class MaxMindGeoIp2Adapter implements GeoIpAdapterInterface
{
    private ?Reader $reader = null;

    public function __construct(
        private readonly string $databasePath,
    ) {}

    public function lookup(string $ipAddress): ?GeoLocation
    {
        if (!$this->isAvailable()) {
            throw new GeoIpException('GeoIP2 database not available');
        }

        try {
            $record = $this->getReader()->city($ipAddress);

            return new GeoLocation(
                countryCode: $record->country->isoCode,
                countryCode3: null, // GeoIP2 doesn't provide ISO 3166-1 alpha-3
                countryName: $record->country->name,
                continentCode: $record->continent->code,
                continentName: $record->continent->name,
                latitude: $record->location->latitude,
                longitude: $record->location->longitude,
                city: $record->city->name,
                postalCode: $record->postal->code,
                region: $record->mostSpecificSubdivision->isoCode,
                regionName: $record->mostSpecificSubdivision->name,
            );
        } catch (AddressNotFoundException) {
            return null;
        }
    }

    public function isAvailable(): bool
    {
        return file_exists($this->databasePath) && is_readable($this->databasePath);
    }

    private function getReader(): Reader
    {
        if ($this->reader === null) {
            $this->reader = new Reader($this->databasePath);
        }
        return $this->reader;
    }
}
```

### GeoLocation DTO

```php
<?php
declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Dto;

/**
 * Immutable value object for geolocation data.
 */
final readonly class GeoLocation
{
    public function __construct(
        public ?string $countryCode = null,
        public ?string $countryCode3 = null,
        public ?string $countryName = null,
        public ?string $continentCode = null,
        public ?string $continentName = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $city = null,
        public ?string $postalCode = null,
        public ?string $region = null,
        public ?string $regionName = null,
    ) {}

    /**
     * Check if location has valid coordinates.
     */
    public function hasCoordinates(): bool
    {
        return $this->latitude !== null
            && $this->longitude !== null
            && !($this->latitude === 0.0 && $this->longitude === 0.0);
    }
}
```

### Country Context Type

```php
<?php
declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Context\Type;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\ContextsGeolocation\Service\GeoIpService;

final class CountryContext extends AbstractContext
{
    public function __construct(
        private readonly GeoIpService $geoIpService,
    ) {}

    public function match(array $arDependencies = []): bool
    {
        // Check session cache first
        [$fromSession, $result] = $this->getMatchFromSession();
        if ($fromSession) {
            return $result;
        }

        $matches = $this->matchCountries();

        return $this->storeInSession($this->invert($matches));
    }

    private function matchCountries(): bool
    {
        $configuredCountries = $this->getConfValue('field_countries', '', 'sDEF');
        if ($configuredCountries === '') {
            return false;
        }

        $allowedCountries = array_map('trim', explode(',', $configuredCountries));
        $location = $this->geoIpService->getLocationForRequest();

        // Handle unknown location
        if ($location === null || $location->countryCode === null) {
            return in_array('*unknown*', $allowedCountries, true);
        }

        // Check both 2-letter and 3-letter codes
        return in_array($location->countryCode, $allowedCountries, true)
            || ($location->countryCode3 !== null
                && in_array($location->countryCode3, $allowedCountries, true));
    }
}
```

### Continent Context Type

```php
<?php
declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Context\Type;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\ContextsGeolocation\Service\GeoIpService;

final class ContinentContext extends AbstractContext
{
    // Continent codes: AF (Africa), AN (Antarctica), AS (Asia),
    // EU (Europe), NA (North America), OC (Oceania), SA (South America)

    public function __construct(
        private readonly GeoIpService $geoIpService,
    ) {}

    public function match(array $arDependencies = []): bool
    {
        [$fromSession, $result] = $this->getMatchFromSession();
        if ($fromSession) {
            return $result;
        }

        $matches = $this->matchContinents();

        return $this->storeInSession($this->invert($matches));
    }

    private function matchContinents(): bool
    {
        $configuredContinents = $this->getConfValue('field_continents', '', 'sDEF');
        if ($configuredContinents === '') {
            return false;
        }

        $allowedContinents = array_map('trim', explode(',', $configuredContinents));
        $location = $this->geoIpService->getLocationForRequest();

        if ($location === null || $location->continentCode === null) {
            return in_array('*unknown*', $allowedContinents, true);
        }

        return in_array($location->continentCode, $allowedContinents, true);
    }
}
```

### Distance Context Type

```php
<?php
declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Context\Type;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\ContextsGeolocation\Service\GeoIpService;

final class DistanceContext extends AbstractContext
{
    private const EARTH_RADIUS_KM = 6371.0;

    public function __construct(
        private readonly GeoIpService $geoIpService,
    ) {}

    public function match(array $arDependencies = []): bool
    {
        [$fromSession, $result] = $this->getMatchFromSession();
        if ($fromSession) {
            return $result;
        }

        $matches = $this->matchDistance();

        return $this->storeInSession($this->invert($matches));
    }

    private function matchDistance(): bool
    {
        $position = $this->getConfValue('field_position', '', 'sDEF');
        $maxDistance = $this->getConfValue('field_distance', '', 'sDEF');
        $allowUnknown = (bool) $this->getConfValue('field_unknown', '', 'sDEF');

        if ($position === '' || $maxDistance === '') {
            return false;
        }

        $location = $this->geoIpService->getLocationForRequest();

        if ($location === null || !$location->hasCoordinates()) {
            return $allowUnknown;
        }

        [$targetLat, $targetLon] = array_map('floatval', explode(',', $position));

        $distance = $this->calculateHaversineDistance(
            $targetLat,
            $targetLon,
            $location->latitude,
            $location->longitude
        );

        return $distance <= (float) $maxDistance;
    }

    /**
     * Calculate distance between two points using Haversine formula.
     *
     * @return float Distance in kilometers
     * @see https://en.wikipedia.org/wiki/Haversine_formula
     */
    private function calculateHaversineDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        $c = 2 * asin(sqrt($a));

        return self::EARTH_RADIUS_KM * $c;
    }
}
```

### GeoIP Service

```php
<?php
declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Service;

use Netresearch\ContextsGeolocation\Adapter\GeoIpAdapterInterface;
use Netresearch\ContextsGeolocation\Dto\GeoLocation;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ServerRequestFactory;

final class GeoIpService
{
    public function __construct(
        private readonly GeoIpAdapterInterface $adapter,
        private readonly bool $trustProxyHeaders = false,
        private readonly array $proxyHeaders = ['X-Forwarded-For', 'X-Real-IP'],
    ) {}

    /**
     * Get geolocation for the current request.
     */
    public function getLocationForRequest(): ?GeoLocation
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
        $ipAddress = $this->getClientIpAddress($request);

        if ($ipAddress === null || $this->isPrivateIp($ipAddress)) {
            return null;
        }

        return $this->adapter->lookup($ipAddress);
    }

    /**
     * Get geolocation for a specific IP address.
     */
    public function getLocationForIp(string $ipAddress): ?GeoLocation
    {
        if ($this->isPrivateIp($ipAddress)) {
            return null;
        }

        return $this->adapter->lookup($ipAddress);
    }

    private function getClientIpAddress(ServerRequestInterface $request): ?string
    {
        if ($this->trustProxyHeaders) {
            foreach ($this->proxyHeaders as $header) {
                $value = $request->getHeaderLine($header);
                if ($value !== '') {
                    // X-Forwarded-For may contain multiple IPs
                    $ips = array_map('trim', explode(',', $value));
                    return $ips[0];
                }
            }
        }

        $serverParams = $request->getServerParams();
        return $serverParams['REMOTE_ADDR'] ?? null;
    }

    private function isPrivateIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
```

### IP Address Handling

```php
// IPv4 examples: 192.0.2.1, 203.0.113.50
// IPv6 examples: 2001:db8::1, ::ffff:192.0.2.1 (IPv4-mapped)

// Private ranges (return null/unknown):
// - 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16 (IPv4 private)
// - fc00::/7 (IPv6 unique local)
// - 127.0.0.0/8, ::1 (loopback)
// - 169.254.0.0/16, fe80::/10 (link-local)
```

## Security & Safety

- Validate IP addresses before lookup
- Handle AddressNotFoundException gracefully
- Never expose raw GeoIP database errors to users
- Cache results in session to minimize lookups
- Use TYPO3's PSR-7 request instead of `$_SERVER` directly

## Testing Patterns

### Unit Tests for Adapters

```php
<?php
declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Tests\Unit\Adapter;

use Netresearch\ContextsGeolocation\Adapter\MaxMindGeoIp2Adapter;
use PHPUnit\Framework\TestCase;

final class MaxMindGeoIp2AdapterTest extends TestCase
{
    public function testIsAvailableReturnsFalseWhenDatabaseMissing(): void
    {
        $adapter = new MaxMindGeoIp2Adapter('/nonexistent/path.mmdb');
        self::assertFalse($adapter->isAvailable());
    }

    public function testLookupReturnsNullForUnknownIp(): void
    {
        // Use test database or mock Reader
        $adapter = $this->createAdapterWithTestDatabase();
        $result = $adapter->lookup('0.0.0.0');
        self::assertNull($result);
    }
}
```

### Unit Tests for Distance Calculation

```php
<?php
declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Tests\Unit\Context\Type;

use PHPUnit\Framework\TestCase;

final class DistanceContextTest extends TestCase
{
    /**
     * @dataProvider distanceDataProvider
     */
    public function testHaversineDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2,
        float $expectedKm,
        float $tolerance
    ): void {
        $context = $this->createDistanceContext();
        $distance = $this->invokeMethod($context, 'calculateHaversineDistance', [
            $lat1, $lon1, $lat2, $lon2
        ]);

        self::assertEqualsWithDelta($expectedKm, $distance, $tolerance);
    }

    public static function distanceDataProvider(): iterable
    {
        // Leipzig to Berlin (~150 km)
        yield 'Leipzig to Berlin' => [
            51.3397, 12.3731,  // Leipzig
            52.5200, 13.4050,  // Berlin
            150.0, 10.0       // ~150 km with 10 km tolerance
        ];

        // Same point
        yield 'Same point' => [
            51.3397, 12.3731,
            51.3397, 12.3731,
            0.0, 0.1
        ];
    }
}
```

### Functional Tests with Test Database

```php
<?php
declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Tests\Functional\Context\Type;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CountryContextFunctionalTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'netresearch/contexts',
        'netresearch/contexts_geolocation',
    ];

    public function testCountryContextMatchesConfiguredCountry(): void
    {
        // Create context record with Germany configured
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CountryContext.csv');

        // Mock GeoIP service to return German IP
        // ... test implementation
    }
}
```

## PR/Commit Checklist

- [ ] `composer lint` passes
- [ ] `composer analyze` passes
- [ ] Unit tests added/updated for new functionality
- [ ] Strict types declared
- [ ] Return types on all methods
- [ ] GeoIP adapter implements interface
- [ ] IP validation handles both IPv4 and IPv6
- [ ] Private IPs handled correctly (return null)

## Good vs Bad Examples

### Adapter Pattern

```php
// Good: Dependency injection with interface
public function __construct(
    private readonly GeoIpAdapterInterface $adapter,
) {}

// Bad: Static singleton (legacy pattern)
$adapter = AbstractAdapter::getInstance($ip);
```

### IP Address Access

```php
// Good: PSR-7 request with configurable proxy trust
$request = $GLOBALS['TYPO3_REQUEST'];
$ip = $this->getClientIpAddress($request);

// Bad: Direct $_SERVER access
$ip = $_SERVER['REMOTE_ADDR'];
```

### Error Handling

```php
// Good: Graceful handling, return null
try {
    return $this->reader->city($ip);
} catch (AddressNotFoundException) {
    return null;
}

// Bad: Let exceptions bubble up
return $this->reader->city($ip); // Throws on unknown IP!
```

### Coordinate Validation

```php
// Good: Check for null AND zero coordinates
if ($location === null || !$location->hasCoordinates()) {
    return $allowUnknown;
}

// Bad: Only check for false (legacy pattern)
if ($arPosition === false) {
    return $bUnknown;
}
```

## House Rules

- GeoIP adapter must be swappable via DI (interface-based)
- All context types must support `*unknown*` handling
- Distance calculations use Haversine formula (great-circle distance)
- Session caching is mandatory for performance
- MaxMind database path configured via extension settings
- Support both GeoLite2 (free) and GeoIP2 (commercial) databases

## When Stuck

- MaxMind GeoIP2 PHP: https://github.com/maxmind/GeoIP2-php
- GeoLite2 databases: https://dev.maxmind.com/geoip/geolite2-free-geolocation-data
- TYPO3 Core API: https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/
- Extension issues: https://github.com/netresearch/t3x-contexts_geolocation/issues
