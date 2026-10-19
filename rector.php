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

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector;
use Ssch\TYPO3Rector\Set\Typo3SetList;

$configure = require_once __DIR__ . '/.Build/vendor/netresearch/typo3-ci-workflows/config/rector/rector.php';

return static function (RectorConfig $rectorConfig) use ($configure): void {
    // Shared org base config: paths (with Tests/), code-quality sets, rule
    // skips, the package's ergebnis-free phpstan-rector.neon, and the PHP and
    // TYPO3 level sets for the lowest supported versions (composer.json:
    // php ^8.2, typo3/cms-core ^12.4 || ^13.4). typo3: 12 keeps the TYPO3
    // migrations at UP_TO_TYPO3_12, because UP_TO_TYPO3_13 introduces
    // v13-only APIs.
    $configure($rectorConfig, __DIR__, [
        'tests' => true,
        'php'   => '8.2',
        'typo3' => 12,
    ]);

    $rectorConfig->sets([
        // TYPO3 code quality and general improvements
        Typo3SetList::CODE_QUALITY,
        Typo3SetList::GENERAL,
    ]);

    $rectorConfig->skip([
        // Skip removing parent calls - may be needed for TYPO3 hooks
        RemoveParentCallWithoutParentRector::class,
    ]);
};
