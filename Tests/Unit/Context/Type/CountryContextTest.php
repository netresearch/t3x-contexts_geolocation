<?php

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Tests\Unit\Context\Type;

use Netresearch\ContextsGeolocation\Adapter\GeoIpAdapterInterface;
use Netresearch\ContextsGeolocation\Context\Type\CountryContext;
use Netresearch\ContextsGeolocation\Dto\GeoLocation;
use Netresearch\ContextsGeolocation\Service\GeoLocationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(CountryContext::class)]
final class CountryContextTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
    }

    #[Test]
    public function matchReturnsTrueWhenCountryMatches(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(countryCode: 'DE'),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableCountryContext(
            'DE, US, FR',
            $service,
        );

        self::assertTrue($context->match());
    }

    #[Test]
    public function matchReturnsFalseWhenCountryDoesNotMatch(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(countryCode: 'GB'),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableCountryContext(
            'DE, US, FR',
            $service,
        );

        self::assertFalse($context->match());
    }

    #[Test]
    public function matchHandlesCaseInsensitiveComparison(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(countryCode: 'de'),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableCountryContext(
            'DE, US, FR',
            $service,
        );

        self::assertTrue($context->match());
    }

    #[Test]
    public function matchReturnsFalseWhenCountryCodeIsNull(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(countryCode: null),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableCountryContext(
            'DE, US, FR',
            $service,
        );

        self::assertFalse($context->match());
    }

    #[Test]
    public function matchReturnsFalseForPrivateIp(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->expects(self::never())->method('lookup');

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '192.168.1.1']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableCountryContext(
            'DE',
            $service,
        );

        self::assertFalse($context->match());
    }

    #[Test]
    public function matchReturnsInvertedResultWhenInvertIsTrue(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(countryCode: 'DE'),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableCountryContext(
            'DE, US, FR',
            $service,
            invert: true,
        );

        // Country matches, but inversion should return false
        self::assertFalse($context->match());
    }

    #[Test]
    public function matchReturnsTrueWhenCountryDoesNotMatchAndInverted(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(countryCode: 'GB'),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableCountryContext(
            'DE, US, FR',
            $service,
            invert: true,
        );

        // Country doesn't match, inversion should return true
        self::assertTrue($context->match());
    }

    #[Test]
    public function matchReturnsFalseWhenNoCountriesConfigured(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(countryCode: 'DE'),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableCountryContext(
            '',
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
        $context = $this->createTestableCountryContext(
            'DE',
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

        $context = $this->createTestableCountryContext(
            'DE, US, FR',
            $service,
        );

        self::assertFalse($context->match());
    }

    #[Test]
    #[DataProvider('countryCodesToMatchDataProvider')]
    public function matchWorksWithVariousCountryCodeFormats(string $configured, string $detected, bool $expected): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(countryCode: $detected),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableCountryContext(
            $configured,
            $service,
        );

        self::assertSame($expected, $context->match());
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function countryCodesToMatchDataProvider(): iterable
    {
        yield 'single country match' => ['DE', 'DE', true];
        yield 'multiple countries with match' => ['DE, US, FR', 'US', true];
        yield 'multiple countries no match' => ['DE, US, FR', 'GB', false];
        yield 'whitespace handling' => ['  DE  ,  US  ', 'DE', true];
        yield 'case insensitive configured' => ['de, us', 'DE', true];
        yield 'case insensitive detected' => ['DE, US', 'de', true];
        yield 'comma-separated no spaces' => ['DE,US,FR', 'US', true];
    }

    /**
     * Create a testable CountryContext that bypasses TYPO3 dependencies.
     */
    private function createTestableCountryContext(
        string $countries,
        GeoLocationService $service,
        bool $invert = false,
    ): CountryContext {
        return new class ($countries, $service, $invert) extends CountryContext {
            private string $testCountries;
            private bool $testInvert;

            public function __construct(
                string $countries,
                GeoLocationService $service,
                bool $invert,
            ) {
                // Skip parent constructor to avoid TYPO3 dependencies
                $this->testCountries = $countries;
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
                if ($fieldName === 'field_countries') {
                    return $this->testCountries;
                }

                return $default;
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
