<?php

/**
 * This file is part of the package netresearch/contexts-geolocation.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Tests\Functional\Context\Type;

use Netresearch\Contexts\Context\Container;
use Netresearch\ContextsGeolocation\Adapter\GeoIpAdapterInterface;
use Netresearch\ContextsGeolocation\Context\Type\CountryContext;
use Netresearch\ContextsGeolocation\Dto\GeoLocation;
use Netresearch\ContextsGeolocation\Service\GeoLocationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional tests for CountryContext.
 *
 * Tests that context types can be loaded from database and match correctly
 * based on visitor geolocation data.
 */
#[CoversClass(CountryContext::class)]
final class CountryContextTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'netresearch/contexts',
        'netresearch/contexts-geolocation',
    ];

    /**
     * @var array<string, mixed>
     */
    private array $originalServer = [];

    protected function setUp(): void
    {
        parent::setUp();

        Container::reset();

        // Backup original $_SERVER values we'll modify
        $this->originalServer = [
            'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? null,
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/tx_contexts_contexts.csv');
    }

    protected function tearDown(): void
    {
        Container::reset();
        unset($GLOBALS['TYPO3_REQUEST']);

        // Restore original $_SERVER values
        foreach ($this->originalServer as $key => $value) {
            if ($value === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $value;
            }
        }

        parent::tearDown();
    }

    #[Test]
    public function countryContextCanBeLoadedFromDatabase(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = new ServerRequest('http://localhost/', 'GET');
        $request = $request->withServerParams(['REMOTE_ADDR' => '8.8.8.8']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        Container::get()
            ->setRequest($request)
            ->initMatching();

        // UID 1 is "Germany Country Context" from fixture
        $context = Container::get()->find(1);

        self::assertNotNull($context, 'Context with UID 1 should exist');
        self::assertSame('geolocation_country', $context->getType());
        self::assertSame('Germany Country Context', $context->getTitle());
        self::assertSame('germany', $context->getAlias());
    }

    #[Test]
    public function countryContextCanBeFoundByAlias(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = new ServerRequest('http://localhost/', 'GET');
        $request = $request->withServerParams(['REMOTE_ADDR' => '8.8.8.8']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        Container::get()
            ->setRequest($request)
            ->initMatching();

        $context = Container::get()->find('germany');

        self::assertNotNull($context, 'Context with alias "germany" should exist');
        self::assertSame(1, $context->getUid());
    }

    #[Test]
    public function multipleCountryContextsCanBeLoaded(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = new ServerRequest('http://localhost/', 'GET');
        $request = $request->withServerParams(['REMOTE_ADDR' => '8.8.8.8']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        Container::get()
            ->setRequest($request)
            ->initMatching();

        // Check that multiple contexts are loaded from fixtures
        $germany = Container::get()->find('germany');
        $multiple = Container::get()->find('multiple_countries');
        $inverted = Container::get()->find('inverted_country');

        self::assertNotNull($germany, 'Germany context should exist');
        self::assertNotNull($multiple, 'Multiple countries context should exist');
        self::assertNotNull($inverted, 'Inverted country context should exist');

        self::assertSame(1, $germany->getUid());
        self::assertSame(2, $multiple->getUid());
        self::assertSame(3, $inverted->getUid());
    }

    #[Test]
    public function disabledContextIsNotLoaded(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = new ServerRequest('http://localhost/', 'GET');
        $request = $request->withServerParams(['REMOTE_ADDR' => '8.8.8.8']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        Container::get()
            ->setRequest($request)
            ->initMatching();

        // UID 4 is disabled in fixture
        $context = Container::get()->find(4);

        self::assertNull($context, 'Disabled context should not be loadable');
    }

    #[Test]
    public function contextTypeIsGeolocationCountry(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = new ServerRequest('http://localhost/', 'GET');
        $request = $request->withServerParams(['REMOTE_ADDR' => '8.8.8.8']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        Container::get()
            ->setRequest($request)
            ->initMatching();

        $context = Container::get()->find(1);

        self::assertNotNull($context);
        self::assertInstanceOf(CountryContext::class, $context);
    }

    #[Test]
    public function countryContextMatchesWithMockedAdapter(): void
    {
        // Create a mock adapter that returns Germany for any public IP
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')
            ->willReturn(new GeoLocation(countryCode: 'DE'));
        $adapter->method('isAvailable')->willReturn(true);

        $service = new GeoLocationService($adapter);

        // Create context directly with mocked service
        $row = [
            'uid' => 100,
            'type' => 'geolocation_country',
            'title' => 'Test Germany',
            'alias' => 'test_germany',
            'tstamp' => time(),
            'invert' => 0,
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
            'type_conf' => '<?xml version="1.0" encoding="utf-8"?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="field_countries"><value index="vDEF">DE</value></field></language></sheet></data></T3FlexForms>',
        ];

        $context = new CountryContext($row, $service);

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = new ServerRequest('http://localhost/', 'GET');
        $request = $request->withServerParams(['REMOTE_ADDR' => '8.8.8.8']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        self::assertTrue(
            $context->match(),
            'Country context should match when visitor country (DE) is in configured list',
        );
    }

    #[Test]
    public function countryContextDoesNotMatchDifferentCountry(): void
    {
        // Create a mock adapter that returns UK
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')
            ->willReturn(new GeoLocation(countryCode: 'GB'));
        $adapter->method('isAvailable')->willReturn(true);

        $service = new GeoLocationService($adapter);

        // Create context configured for Germany
        $row = [
            'uid' => 100,
            'type' => 'geolocation_country',
            'title' => 'Test Germany',
            'alias' => 'test_germany',
            'tstamp' => time(),
            'invert' => 0,
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
            'type_conf' => '<?xml version="1.0" encoding="utf-8"?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="field_countries"><value index="vDEF">DE</value></field></language></sheet></data></T3FlexForms>',
        ];

        $context = new CountryContext($row, $service);

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = new ServerRequest('http://localhost/', 'GET');
        $request = $request->withServerParams(['REMOTE_ADDR' => '8.8.8.8']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        self::assertFalse(
            $context->match(),
            'Country context should not match when visitor country (GB) is not in configured list (DE)',
        );
    }

    #[Test]
    public function invertedCountryContextInvertsMatchResult(): void
    {
        // Create a mock adapter that returns UK
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')
            ->willReturn(new GeoLocation(countryCode: 'GB'));
        $adapter->method('isAvailable')->willReturn(true);

        $service = new GeoLocationService($adapter);

        // Create inverted context configured for GB
        $row = [
            'uid' => 100,
            'type' => 'geolocation_country',
            'title' => 'Test Inverted',
            'alias' => 'test_inverted',
            'tstamp' => time(),
            'invert' => 1, // Inverted!
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
            'type_conf' => '<?xml version="1.0" encoding="utf-8"?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="field_countries"><value index="vDEF">GB</value></field></language></sheet></data></T3FlexForms>',
        ];

        $context = new CountryContext($row, $service);

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = new ServerRequest('http://localhost/', 'GET');
        $request = $request->withServerParams(['REMOTE_ADDR' => '8.8.8.8']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        // Normal match would be true (GB in GB), but inverted should be false
        self::assertFalse(
            $context->match(),
            'Inverted country context should return false when country matches',
        );
    }

    #[Test]
    public function countryContextMatchesMultipleCountries(): void
    {
        // Create a mock adapter that returns France
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')
            ->willReturn(new GeoLocation(countryCode: 'FR'));
        $adapter->method('isAvailable')->willReturn(true);

        $service = new GeoLocationService($adapter);

        // Create context configured for DE, US, FR
        $row = [
            'uid' => 100,
            'type' => 'geolocation_country',
            'title' => 'Test Multiple',
            'alias' => 'test_multiple',
            'tstamp' => time(),
            'invert' => 0,
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
            'type_conf' => '<?xml version="1.0" encoding="utf-8"?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="field_countries"><value index="vDEF">DE,US,FR</value></field></language></sheet></data></T3FlexForms>',
        ];

        $context = new CountryContext($row, $service);

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = new ServerRequest('http://localhost/', 'GET');
        $request = $request->withServerParams(['REMOTE_ADDR' => '8.8.8.8']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        self::assertTrue(
            $context->match(),
            'Country context should match when visitor country (FR) is in list (DE,US,FR)',
        );
    }

    #[Test]
    public function countryContextDoesNotMatchPrivateIp(): void
    {
        // Adapter should not be called for private IPs
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->expects(self::never())->method('lookup');
        $adapter->method('isAvailable')->willReturn(true);

        $service = new GeoLocationService($adapter);

        $row = [
            'uid' => 100,
            'type' => 'geolocation_country',
            'title' => 'Test',
            'alias' => 'test',
            'tstamp' => time(),
            'invert' => 0,
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
            'type_conf' => '<?xml version="1.0" encoding="utf-8"?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="field_countries"><value index="vDEF">DE</value></field></language></sheet></data></T3FlexForms>',
        ];

        $context = new CountryContext($row, $service);

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1'; // Private IP

        $request = new ServerRequest('http://localhost/', 'GET');
        $request = $request->withServerParams(['REMOTE_ADDR' => '192.168.1.1']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        self::assertFalse(
            $context->match(),
            'Country context should not match for private IP addresses',
        );
    }
}
