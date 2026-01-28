<?php

/**
 * This file is part of the package netresearch/contexts-geolocation.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

use Netresearch\Contexts\Api\Configuration;
use Netresearch\ContextsGeolocation\Context\Type\ContinentContext;
use Netresearch\ContextsGeolocation\Context\Type\CountryContext;
use Netresearch\ContextsGeolocation\Context\Type\DistanceContext;

defined('TYPO3') || die('Access denied.');

/**
 * Register geolocation context types with the base contexts extension.
 *
 * These context types allow content targeting based on visitor location:
 * - Country: Match visitors from specific countries (ISO 3166-1 alpha-2 codes)
 * - Continent: Match visitors from specific continents
 * - Distance: Match visitors within a radius of a geographic point
 */

Configuration::registerContextType(
    'geolocation_country',
    'LLL:EXT:contexts_geolocation/Resources/Private/Language/locallang.xlf:context.type.country',
    CountryContext::class,
    'FILE:EXT:contexts_geolocation/Configuration/FlexForms/Country.xml',
);

Configuration::registerContextType(
    'geolocation_continent',
    'LLL:EXT:contexts_geolocation/Resources/Private/Language/locallang.xlf:context.type.continent',
    ContinentContext::class,
    'FILE:EXT:contexts_geolocation/Configuration/FlexForms/Continent.xml',
);

Configuration::registerContextType(
    'geolocation_distance',
    'LLL:EXT:contexts_geolocation/Resources/Private/Language/locallang.xlf:context.type.distance',
    DistanceContext::class,
    'FILE:EXT:contexts_geolocation/Configuration/FlexForms/Distance.xml',
);
