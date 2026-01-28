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
use Netresearch\ContextsGeolocation\Context\Type\ContinentContext;
use Netresearch\ContextsGeolocation\Dto\GeoLocation;
use Netresearch\ContextsGeolocation\Service\GeoLocationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional tests for ContinentContext.
 *
 * Tests that continent context types can be loaded from database and match correctly
 * based on visitor geolocation data.
 */
#[CoversClass(ContinentContext::class)]
final class ContinentContextTest extends FunctionalTestCase
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

        // Backup original $_SERVER values
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
    public function continentContextCanBeLoadedFromDatabase(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = new ServerRequest('http://localhost/', 'GET');
        $request = $request->withServerParams(['REMOTE_ADDR' => '8.8.8.8']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        Container::get()
            ->setRequest($request)
            ->initMatching();

        // UID 5 is "Europe Continent Context" from fixture
        $context = Container::get()->find(5);

        self::assertNotNull($context, 'Context with UID 5 should exist');
        self::assertSame('geolocation_continent', $context->getType());
        self::assertSame('Europe Continent Context', $context->getTitle());
        self::assertSame('europe', $context->getAlias());
    }

    #[Test]
    public function continentContextCanBeFoundByAlias(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = new ServerRequest('http://localhost/', 'GET');
        $request = $request->withServerParams(['REMOTE_ADDR' => '8.8.8.8']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        Container::get()
            ->setRequest($request)
            ->initMatching();

        $context = Container::get()->find('europe');

        self::assertNotNull($context, 'Context with alias "europe" should exist');
        self::assertSame(5, $context->getUid());
    }

    #[Test]
    public function contextTypeIsGeolocationContinent(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = new ServerRequest('http://localhost/', 'GET');
        $request = $request->withServerParams(['REMOTE_ADDR' => '8.8.8.8']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        Container::get()
            ->setRequest($request)
            ->initMatching();

        $context = Container::get()->find(5);

        self::assertNotNull($context);
        self::assertInstanceOf(ContinentContext::class, $context);
    }

    #[Test]
    public function continentContextMatchesWithMockedAdapter(): void
    {
        // Create a mock adapter that returns Europe
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')
            ->willReturn(new GeoLocation(continentCode: 'EU'));
        $adapter->method('isAvailable')->willReturn(true);

        $service = new GeoLocationService($adapter);

        $row = [
            'uid' => 100,
            'type' => 'geolocation_continent',
            'title' => 'Test Europe',
            'alias' => 'test_europe',
            'tstamp' => time(),
            'invert' => 0,
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
            'type_conf' => '<?xml version="1.0" encoding="utf-8"?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="field_continents"><value index="vDEF">EU</value></field></language></sheet></data></T3FlexForms>',
        ];

        $context = new ContinentContext($row, $service);

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = new ServerRequest('http://localhost/', 'GET');
        $request = $request->withServerParams(['REMOTE_ADDR' => '8.8.8.8']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        self::assertTrue(
            $context->match(),
            'Continent context should match when visitor continent (EU) is in configured list',
        );
    }

    #[Test]
    public function continentContextDoesNotMatchDifferentContinent(): void
    {
        // Create a mock adapter that returns Asia
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')
            ->willReturn(new GeoLocation(continentCode: 'AS'));
        $adapter->method('isAvailable')->willReturn(true);

        $service = new GeoLocationService($adapter);

        $row = [
            'uid' => 100,
            'type' => 'geolocation_continent',
            'title' => 'Test Europe',
            'alias' => 'test_europe',
            'tstamp' => time(),
            'invert' => 0,
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
            'type_conf' => '<?xml version="1.0" encoding="utf-8"?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="field_continents"><value index="vDEF">EU</value></field></language></sheet></data></T3FlexForms>',
        ];

        $context = new ContinentContext($row, $service);

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = new ServerRequest('http://localhost/', 'GET');
        $request = $request->withServerParams(['REMOTE_ADDR' => '8.8.8.8']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        self::assertFalse(
            $context->match(),
            'Continent context should not match when visitor continent (AS) is not in configured list (EU)',
        );
    }

    #[Test]
    public function continentContextMatchesMultipleContinents(): void
    {
        // Create a mock adapter that returns North America
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')
            ->willReturn(new GeoLocation(continentCode: 'NA'));
        $adapter->method('isAvailable')->willReturn(true);

        $service = new GeoLocationService($adapter);

        $row = [
            'uid' => 100,
            'type' => 'geolocation_continent',
            'title' => 'Test Multiple',
            'alias' => 'test_multiple',
            'tstamp' => time(),
            'invert' => 0,
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
            'type_conf' => '<?xml version="1.0" encoding="utf-8"?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="field_continents"><value index="vDEF">EU,NA</value></field></language></sheet></data></T3FlexForms>',
        ];

        $context = new ContinentContext($row, $service);

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = new ServerRequest('http://localhost/', 'GET');
        $request = $request->withServerParams(['REMOTE_ADDR' => '8.8.8.8']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        self::assertTrue(
            $context->match(),
            'Continent context should match when visitor continent (NA) is in list (EU,NA)',
        );
    }

    #[Test]
    public function continentContextDoesNotMatchPrivateIp(): void
    {
        // Adapter should not be called for private IPs
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->expects(self::never())->method('lookup');
        $adapter->method('isAvailable')->willReturn(true);

        $service = new GeoLocationService($adapter);

        $row = [
            'uid' => 100,
            'type' => 'geolocation_continent',
            'title' => 'Test',
            'alias' => 'test',
            'tstamp' => time(),
            'invert' => 0,
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
            'type_conf' => '<?xml version="1.0" encoding="utf-8"?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="field_continents"><value index="vDEF">EU</value></field></language></sheet></data></T3FlexForms>',
        ];

        $context = new ContinentContext($row, $service);

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1'; // Private IP

        $request = new ServerRequest('http://localhost/', 'GET');
        $request = $request->withServerParams(['REMOTE_ADDR' => '10.0.0.1']);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        self::assertFalse(
            $context->match(),
            'Continent context should not match for private IP addresses',
        );
    }
}
