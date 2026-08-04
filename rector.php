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
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;

$configure = require_once __DIR__ . '/.Build/vendor/netresearch/typo3-ci-workflows/config/rector/rector.php';

return static function (RectorConfig $rectorConfig) use ($configure): void {
    // Shared org base config: code-quality sets, rule skips, phpstan-rector.neon
    $configure($rectorConfig, __DIR__);

    // paths() replaces the shared list — re-declared to keep Tests/ in scope,
    // which the shared $projectRoot default leaves out.
    $rectorConfig->paths(array_merge(
        [
            __DIR__ . '/Classes',
            __DIR__ . '/Configuration',
            __DIR__ . '/Resources',
            __DIR__ . '/Tests',
        ],
        glob(__DIR__ . '/ext_*.php') ?: [],
    ));

    $rectorConfig->sets([
        // TYPO3 v12 migrations only (extension supports ^12.4 || ^13.4)
        // Note: Don't use UP_TO_TYPO3_13 as it introduces v13-only APIs
        Typo3LevelSetList::UP_TO_TYPO3_12,

        // TYPO3 code quality and general improvements
        Typo3SetList::CODE_QUALITY,
        Typo3SetList::GENERAL,
    ]);

    $rectorConfig->skip([
        // Skip removing parent calls - may be needed for TYPO3 hooks
        RemoveParentCallWithoutParentRector::class,
    ]);
};
