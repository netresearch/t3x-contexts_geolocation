<?php

/*
 * Copyright (c) 2025-2026 Netresearch DTT GmbH
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

// Included by Build/phpstan.neon. CI removes netresearch/typo3-ci-workflows
// on the TYPO3 ^12.4 cells (remove-dev-deps in .github/workflows/ci.yml),
// because its saschaegerer/phpstan-typo3 requires typo3/cms-core ^13.4.3.
// A plain NEON include of the shared config would fail there on a missing
// file, so the include is made only when the package is installed. Without
// it, the parameters the old Build/phpstan.neon set keep the ^12.4 cells at
// the same level as before; reportUnmatchedIgnoredErrors follows the shared
// config, so a stale ignore fails on every cell.
$shared = __DIR__ . '/../.Build/vendor/netresearch/typo3-ci-workflows/config/phpstan/phpstan.neon';

if (is_file($shared)) {
    return ['includes' => [$shared]];
}

return [
    'parameters' => [
        'level'                        => 10,
        'treatPhpDocTypesAsCertain'    => false,
        'reportUnmatchedIgnoredErrors' => true,
        // The explicit analyseAndScan key is needed here: a plain list that
        // works in NEON arrives empty from a PHP config file.
        'excludePaths' => ['analyseAndScan' => [
            '%currentWorkingDirectory%/.Build/*',
            '%currentWorkingDirectory%/ext_emconf.php',
        ]],
    ],
];
