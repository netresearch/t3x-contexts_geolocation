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
use Netresearch\ContextsGeolocation\Context\Type\DistanceContext;
use Netresearch\ContextsGeolocation\Dto\GeoLocation;
use Netresearch\ContextsGeolocation\Service\GeoLocationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional tests for DistanceContext.
 *
 * Tests that distance context types can be loaded from database and match correctly
 * based on visitor geolocation data and configured radius.
 */
#[CoversClass(DistanceContext::class)]
final class DistanceContextTest extends FunctionalTestCase
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
    public function distanceContextCanBeLoadedFromDatabase(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = $this->createFrontendRequest('8.8.8.8');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        // Use initAll() to load all contexts without matching filter
        Container::get()
            ->setRequest($request)
            ->initAll();

        // UID 7 is "Leipzig Distance Context" from fixture
        $context = Container::get()->find(7);

        self::assertNotNull($context, 'Context with UID 7 should exist');
        self::assertSame('geolocation_distance', $context->getType());
        self::assertSame('Leipzig Distance Context', $context->getTitle());
        self::assertSame('leipzig', $context->getAlias());
    }

    #[Test]
    public function distanceContextCanBeFoundByAlias(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = $this->createFrontendRequest('8.8.8.8');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        // Use initAll() to load all contexts without matching filter
        Container::get()
            ->setRequest($request)
            ->initAll();

        $context = Container::get()->find('leipzig');

        self::assertNotNull($context, 'Context with alias "leipzig" should exist');
        self::assertSame(7, $context->getUid());
    }

    #[Test]
    public function contextTypeIsGeolocationDistance(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = $this->createFrontendRequest('8.8.8.8');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        // Use initAll() to load all contexts without matching filter
        Container::get()
            ->setRequest($request)
            ->initAll();

        $context = Container::get()->find(7);

        self::assertNotNull($context);
        self::assertInstanceOf(DistanceContext::class, $context);
    }

    #[Test]
    public function distanceContextMatchesWhenWithinRadius(): void
    {
        // Create a mock adapter that returns Leipzig coordinates (within 100km of Leipzig)
        // Leipzig: 51.3397, 12.3731
        // Return a point 50km away (approximately)
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')
            ->willReturn(new GeoLocation(
                latitude: 51.5, // About 20km north of Leipzig
                longitude: 12.4,
            ));
        $adapter->method('isAvailable')->willReturn(true);

        $service = new GeoLocationService($adapter);

        // Leipzig: 51.3397, 12.3731 with 100km radius
        $row = [
            'uid' => 100,
            'type' => 'geolocation_distance',
            'title' => 'Test Leipzig',
            'alias' => 'test_leipzig',
            'tstamp' => time(),
            'invert' => 0,
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
            'type_conf' => '<?xml version="1.0" encoding="utf-8"?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="field_latitude"><value index="vDEF">51.3397</value></field><field index="field_longitude"><value index="vDEF">12.3731</value></field><field index="field_radius"><value index="vDEF">100</value></field></language></sheet></data></T3FlexForms>',
        ];

        $context = new DistanceContext($row, $service);

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = $this->createFrontendRequest('8.8.8.8');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        self::assertTrue(
            $context->match(),
            'Distance context should match when visitor is within 100km radius of Leipzig',
        );
    }

    #[Test]
    public function distanceContextDoesNotMatchWhenOutsideRadius(): void
    {
        // Create a mock adapter that returns Berlin coordinates (>150km from Leipzig)
        // Leipzig: 51.3397, 12.3731
        // Berlin: 52.52, 13.405 (about 165km from Leipzig)
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')
            ->willReturn(new GeoLocation(
                latitude: 52.52,
                longitude: 13.405,
            ));
        $adapter->method('isAvailable')->willReturn(true);

        $service = new GeoLocationService($adapter);

        // Leipzig with 100km radius
        $row = [
            'uid' => 100,
            'type' => 'geolocation_distance',
            'title' => 'Test Leipzig',
            'alias' => 'test_leipzig',
            'tstamp' => time(),
            'invert' => 0,
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
            'type_conf' => '<?xml version="1.0" encoding="utf-8"?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="field_latitude"><value index="vDEF">51.3397</value></field><field index="field_longitude"><value index="vDEF">12.3731</value></field><field index="field_radius"><value index="vDEF">100</value></field></language></sheet></data></T3FlexForms>',
        ];

        $context = new DistanceContext($row, $service);

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = $this->createFrontendRequest('8.8.8.8');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        self::assertFalse(
            $context->match(),
            'Distance context should not match when visitor (Berlin) is outside 100km radius of Leipzig',
        );
    }

    #[Test]
    public function distanceContextMatchesAtExactBoundary(): void
    {
        // Create a mock adapter that returns a point exactly at the boundary
        // Leipzig: 51.3397, 12.3731, radius 100km
        // A point 100km north would be approximately at latitude 52.24
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')
            ->willReturn(new GeoLocation(
                latitude: 52.239, // ~100km from Leipzig
                longitude: 12.3731,
            ));
        $adapter->method('isAvailable')->willReturn(true);

        $service = new GeoLocationService($adapter);

        $row = [
            'uid' => 100,
            'type' => 'geolocation_distance',
            'title' => 'Test',
            'alias' => 'test',
            'tstamp' => time(),
            'invert' => 0,
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
            'type_conf' => '<?xml version="1.0" encoding="utf-8"?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="field_latitude"><value index="vDEF">51.3397</value></field><field index="field_longitude"><value index="vDEF">12.3731</value></field><field index="field_radius"><value index="vDEF">100</value></field></language></sheet></data></T3FlexForms>',
        ];

        $context = new DistanceContext($row, $service);

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = $this->createFrontendRequest('8.8.8.8');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        self::assertTrue(
            $context->match(),
            'Distance context should match when visitor is exactly at radius boundary',
        );
    }

    #[Test]
    public function distanceContextDoesNotMatchPrivateIp(): void
    {
        // Adapter should not be called for private IPs
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->expects(self::never())->method('lookup');
        $adapter->method('isAvailable')->willReturn(true);

        $service = new GeoLocationService($adapter);

        $row = [
            'uid' => 100,
            'type' => 'geolocation_distance',
            'title' => 'Test',
            'alias' => 'test',
            'tstamp' => time(),
            'invert' => 0,
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
            'type_conf' => '<?xml version="1.0" encoding="utf-8"?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="field_latitude"><value index="vDEF">51.3397</value></field><field index="field_longitude"><value index="vDEF">12.3731</value></field><field index="field_radius"><value index="vDEF">100</value></field></language></sheet></data></T3FlexForms>',
        ];

        $context = new DistanceContext($row, $service);

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '172.16.0.1'; // Private IP

        $request = $this->createFrontendRequest('172.16.0.1');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        self::assertFalse(
            $context->match(),
            'Distance context should not match for private IP addresses',
        );
    }

    #[Test]
    public function distanceContextDoesNotMatchWhenNoCoordinatesAvailable(): void
    {
        // Create a mock adapter that returns location without coordinates
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')
            ->willReturn(new GeoLocation(
                countryCode: 'DE',
                // No latitude/longitude
            ));
        $adapter->method('isAvailable')->willReturn(true);

        $service = new GeoLocationService($adapter);

        $row = [
            'uid' => 100,
            'type' => 'geolocation_distance',
            'title' => 'Test',
            'alias' => 'test',
            'tstamp' => time(),
            'invert' => 0,
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
            'type_conf' => '<?xml version="1.0" encoding="utf-8"?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="field_latitude"><value index="vDEF">51.3397</value></field><field index="field_longitude"><value index="vDEF">12.3731</value></field><field index="field_radius"><value index="vDEF">100</value></field></language></sheet></data></T3FlexForms>',
        ];

        $context = new DistanceContext($row, $service);

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = $this->createFrontendRequest('8.8.8.8');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        self::assertFalse(
            $context->match(),
            'Distance context should not match when GeoIP returns no coordinates',
        );
    }

    #[Test]
    public function distanceContextDoesNotMatchWithInvalidConfiguration(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('lookup')
            ->willReturn(new GeoLocation(latitude: 51.5, longitude: 12.4));
        $adapter->method('isAvailable')->willReturn(true);

        $service = new GeoLocationService($adapter);

        // Invalid: missing latitude
        $row = [
            'uid' => 100,
            'type' => 'geolocation_distance',
            'title' => 'Test',
            'alias' => 'test',
            'tstamp' => time(),
            'invert' => 0,
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
            'type_conf' => '<?xml version="1.0" encoding="utf-8"?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="field_latitude"><value index="vDEF"></value></field><field index="field_longitude"><value index="vDEF">12.3731</value></field><field index="field_radius"><value index="vDEF">100</value></field></language></sheet></data></T3FlexForms>',
        ];

        $context = new DistanceContext($row, $service);

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $request = $this->createFrontendRequest('8.8.8.8');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        self::assertFalse(
            $context->match(),
            'Distance context should not match with invalid (missing latitude) configuration',
        );
    }

    #[Test]
    public function haversineDistanceCalculationIsAccurate(): void
    {
        $adapter = $this->createMock(GeoIpAdapterInterface::class);
        $adapter->method('isAvailable')->willReturn(true);

        $service = new GeoLocationService($adapter);

        $row = [
            'uid' => 100,
            'type' => 'geolocation_distance',
            'title' => 'Test',
            'alias' => 'test',
            'tstamp' => time(),
            'invert' => 0,
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
            'type_conf' => '<?xml version="1.0" encoding="utf-8"?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="field_latitude"><value index="vDEF">0</value></field><field index="field_longitude"><value index="vDEF">0</value></field><field index="field_radius"><value index="vDEF">100</value></field></language></sheet></data></T3FlexForms>',
        ];

        $context = new DistanceContext($row, $service);

        // Test known distances
        // Leipzig to Berlin: approximately 149km (verified with haversine formula)
        $leipzigToBerlin = $context->calculateHaversineDistance(
            51.3397,
            12.3731,
            52.52,
            13.405,
        );

        self::assertGreaterThan(145.0, $leipzigToBerlin);
        self::assertLessThan(155.0, $leipzigToBerlin);

        // Same point should be 0km
        $samePoint = $context->calculateHaversineDistance(
            51.3397,
            12.3731,
            51.3397,
            12.3731,
        );

        self::assertEqualsWithDelta(0.0, $samePoint, 0.001);
    }

    /**
     * Create a ServerRequest configured for frontend mode.
     *
     * @param array<string, string> $serverParams
     */
    protected function createFrontendRequest(string $remoteAddr = '8.8.8.8', array $serverParams = []): ServerRequest
    {
        $serverParams['REMOTE_ADDR'] = $remoteAddr;

        return (new ServerRequest(
            uri: 'http://localhost/',
            method: 'GET',
            serverParams: $serverParams,
        ))->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
    }
}
