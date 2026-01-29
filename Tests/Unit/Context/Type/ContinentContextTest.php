<?php

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Tests\Unit\Context\Type;

use Netresearch\ContextsGeolocation\Adapter\GeoIpAdapterInterface;
use Netresearch\ContextsGeolocation\Context\Type\ContinentContext;
use Netresearch\ContextsGeolocation\Dto\GeoLocation;
use Netresearch\ContextsGeolocation\Service\GeoLocationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(ContinentContext::class)]
final class ContinentContextTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function continentCodesToMatchDataProvider(): iterable
    {
        // Valid continent codes: AF (Africa), AN (Antarctica), AS (Asia), EU (Europe),
        // NA (North America), OC (Oceania), SA (South America)
        yield 'Africa' => ['AF', 'AF', true];
        yield 'Antarctica' => ['AN', 'AN', true];
        yield 'Asia' => ['AS', 'AS', true];
        yield 'Europe' => ['EU', 'EU', true];
        yield 'North America' => ['NA', 'NA', true];
        yield 'Oceania' => ['OC', 'OC', true];
        yield 'South America' => ['SA', 'SA', true];
        yield 'multiple continents with match' => ['EU, NA, AS', 'NA', true];
        yield 'multiple continents no match' => ['EU, NA', 'SA', false];
        yield 'whitespace handling' => ['  EU  ,  NA  ', 'EU', true];
        yield 'case insensitive configured' => ['eu, na', 'EU', true];
        yield 'case insensitive detected' => ['EU, NA', 'eu', true];
    }

    #[Test]
    public function matchReturnsTrueWhenContinentMatches(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(continentCode: 'EU'),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableContinentContext(
            'EU, NA',
            $service,
        );

        self::assertTrue($context->match());
    }

    #[Test]
    public function matchReturnsFalseWhenContinentDoesNotMatch(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(continentCode: 'AS'),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableContinentContext(
            'EU, NA',
            $service,
        );

        self::assertFalse($context->match());
    }

    #[Test]
    public function matchReturnsFalseWhenContinentCodeIsNull(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(continentCode: null),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableContinentContext(
            'EU',
            $service,
        );

        self::assertFalse($context->match());
    }

    #[Test]
    public function matchReturnsInvertedResultWhenInvertIsTrue(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(continentCode: 'EU'),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableContinentContext(
            'EU, NA',
            $service,
            invert: true,
        );

        // Continent matches, but inversion should return false
        self::assertFalse($context->match());
    }

    #[Test]
    public function matchReturnsFalseForPrivateIp(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->expects(self::never())->method('lookup');

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '10.0.0.1']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableContinentContext(
            'EU',
            $service,
        );

        self::assertFalse($context->match());
    }

    #[Test]
    public function matchReturnsFalseWhenNoContinentsConfigured(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(continentCode: 'EU'),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableContinentContext(
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
        $context = $this->createTestableContinentContext(
            'EU',
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

        $context = $this->createTestableContinentContext(
            'EU',
            $service,
        );

        self::assertFalse($context->match());
    }

    #[Test]
    #[DataProvider('continentCodesToMatchDataProvider')]
    public function matchWorksWithValidContinentCodes(string $configured, string $detected, bool $expected): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')->with('8.8.8.8')->willReturn(
            new GeoLocation(continentCode: $detected),
        );

        $service = new GeoLocationService($adapter);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => '8.8.8.8']);
        $request->method('getHeaderLine')->willReturn('');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->createTestableContinentContext(
            $configured,
            $service,
        );

        self::assertSame($expected, $context->match());
    }

    #[Test]
    public function getValidContinentCodesReturnsAllCodes(): void
    {
        $codes = ContinentContext::getValidContinentCodes();

        self::assertContains('AF', $codes);
        self::assertContains('AN', $codes);
        self::assertContains('AS', $codes);
        self::assertContains('EU', $codes);
        self::assertContains('NA', $codes);
        self::assertContains('OC', $codes);
        self::assertContains('SA', $codes);
        self::assertCount(7, $codes);
    }

    /**
     * Create a testable ContinentContext that bypasses TYPO3 dependencies.
     */
    private function createTestableContinentContext(
        string $continents,
        GeoLocationService $service,
        bool $invert = false,
    ): ContinentContext {
        return new class ($continents, $service, $invert) extends ContinentContext {
            private string $testContinents;

            private bool $testInvert;

            public function __construct(
                string $continents,
                GeoLocationService $service,
                bool $invert,
            ) {
                // Skip parent constructor to avoid TYPO3 dependencies
                $this->testContinents = $continents;
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
                if ($fieldName === 'field_continents') {
                    return $this->testContinents;
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
