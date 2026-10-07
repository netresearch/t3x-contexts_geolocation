<?php

/*
 * Copyright (c) 2025-2026 Netresearch DTT GmbH
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * This file is part of the package netresearch/contexts-geolocation.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Tests\Functional\Context\Type;

use Netresearch\ContextsGeolocation\Context\Type\CountryContext;
use Netresearch\ContextsGeolocation\Service\GeoLocationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional tests for the lazy service fallback in AbstractGeolocationContext.
 *
 * Context types are built by netresearch/contexts Factory::createFromDb() via
 * GeneralUtility::makeInstance() with only the database row, so the lazy
 * container lookup is the production path. It requires GeoLocationService to
 * be public in Configuration/Services.yaml; these tests pin that. Without the
 * `public: true` entry the lookup would throw, the catch in
 * getGeoLocationService() would swallow it and every geolocation context would
 * silently stop matching (see netresearch/t3x-contexts_wurfl#43 for the same
 * defect class in the sibling extension).
 */
#[CoversClass(CountryContext::class)]
final class AbstractGeolocationContextTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'netresearch/contexts',
        'netresearch/contexts-geolocation',
    ];

    /**
     * @var array<string, string|false>
     */
    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();

        // GeoLocationService and its adapter resolve their configuration from
        // env placeholders in Services.yaml at container lookup time.
        // GEOIP_TRUST_PROXY_HEADERS is set to show that it no longer has an effect.
        foreach (['GEOIP_DATABASE_PATH' => '/dev/null', 'GEOIP_TRUST_PROXY_HEADERS' => '1'] as $name => $value) {
            $this->originalEnv[$name] = getenv($name);
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->originalEnv as $name => $value) {
            if ($value === false) {
                putenv($name);
                unset($_ENV[$name]);
            } else {
                putenv($name . '=' . $value);
                $_ENV[$name] = $value;
            }
        }

        parent::tearDown();
    }

    /**
     * The client IP address is the one TYPO3 determines: X-Forwarded-For
     * counts only when REMOTE_ADDR is listed in SYS/reverseProxyIP.
     *
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function reverseProxyConfigurationProvider(): iterable
    {
        yield 'no reverse proxy configured' => [['reverseProxyIP' => ''], '203.0.113.10'];
        yield 'REMOTE_ADDR is the configured reverse proxy' => [
            ['reverseProxyIP' => '203.0.113.10', 'reverseProxyHeaderMultiValue' => 'first'],
            '198.51.100.7',
        ];
    }

    /**
     * Guards the `public: true` entry in Configuration/Services.yaml. The
     * container of a booted TYPO3 exposes only public services through has(),
     * so this really asserts public visibility — a private service would make
     * the lazy fallback in getGeoLocationService() return null forever.
     */
    #[Test]
    public function geoLocationServiceIsRetrievableFromTheContainer(): void
    {
        self::assertTrue(
            $this->getContainer()->has(GeoLocationService::class),
            'GeoLocationService must be public so context types can resolve it from the container',
        );
    }

    /**
     * @param array<string, string> $systemConfiguration
     */
    #[Test]
    #[DataProvider('reverseProxyConfigurationProvider')]
    public function clientIpAddressFollowsTheTypo3ReverseProxyConfiguration(
        array $systemConfiguration,
        string $expectedAddress,
    ): void {
        $service = $this->getContainer()->get(GeoLocationService::class);
        self::assertInstanceOf(GeoLocationService::class, $service);

        $serverParams = ['REMOTE_ADDR' => '203.0.113.10', 'HTTP_X_FORWARDED_FOR' => '198.51.100.7'];
        $request = (new ServerRequest(
            'https://example.org/',
            'GET',
            'php://temp',
            ['X-Forwarded-For' => '198.51.100.7'],
            $serverParams,
        ))->withAttribute(
            'normalizedParams',
            NormalizedParams::createFromServerParams($serverParams, $systemConfiguration),
        );

        self::assertSame($expectedAddress, $service->getClientIpAddress($request));
    }

    /**
     * A missing database is a configuration error, not a reason to fail every
     * request that evaluates a geolocation context.
     */
    #[Test]
    public function lookupWithoutDatabaseYieldsNoLocation(): void
    {
        putenv('GEOIP_DATABASE_PATH=/nonexistent/GeoLite2-City.mmdb');
        $_ENV['GEOIP_DATABASE_PATH'] = '/nonexistent/GeoLite2-City.mmdb';

        $service = $this->getContainer()->get(GeoLocationService::class);
        self::assertInstanceOf(GeoLocationService::class, $service);

        self::assertNull($service->getLocationForIp('8.8.8.8'));
    }

    #[Test]
    public function contextWithoutInjectedServiceResolvesOneLazily(): void
    {
        // Mirrors Factory::createFromDb(): only the row, no service.
        $context = new class ([]) extends CountryContext {
            public function resolveGeoLocationService(): ?GeoLocationService
            {
                return $this->getGeoLocationService();
            }
        };

        self::assertInstanceOf(
            GeoLocationService::class,
            $context->resolveGeoLocationService(),
            'Lazy fallback must resolve the service from the container, not degrade to null',
        );
    }
}
