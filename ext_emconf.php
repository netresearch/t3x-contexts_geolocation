<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Contexts: Geolocation',
    'description' => 'Geolocation-based context types (continent, country, distance) for the contexts extension. Uses MaxMind GeoIP2 for IP-based location detection - by Netresearch.',
    'category' => 'misc',
    'author' => 'Netresearch DTT GmbH',
    'author_email' => 'typo3@netresearch.de',
    'author_company' => 'Netresearch DTT GmbH',
    'state' => 'stable',
    'version' => '2.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'php' => '8.2.0-8.5.99',
            'contexts' => '4.0.0-4.99.99',
        ],
        'conflicts' => [],
        'suggests' => [
            'static_info_tables' => '',
        ],
    ],
];
