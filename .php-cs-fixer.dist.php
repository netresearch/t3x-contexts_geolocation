<?php

/*
 * Copyright (c) 2025-2026 Netresearch DTT GmbH
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

$createConfig = require __DIR__ . '/.Build/vendor/netresearch/typo3-ci-workflows/config/php-cs-fixer/config.php';

// Rules this extension enforced before the shared set existed and that the
// shared set does not cover (or covers with fewer elements). They are kept
// because they are stricter; every other rule comes from the shared set.
return $createConfig(
    "Copyright (c) 2025-2026 Netresearch DTT GmbH\nSPDX-License-Identifier: AGPL-3.0-or-later",
    __DIR__,
    [
        // Strict typing and comparisons
        'strict_comparison' => true,
        'strict_param'      => true,
        'void_return'       => true,

        // Modernisation
        'combine_nested_dirname'                => true,
        'get_class_to_class_keyword'            => true,
        'implode_call'                          => true,
        'modernize_strpos'                      => true,
        'no_php4_constructor'                   => true,
        'no_unreachable_default_argument_value' => true,
        'non_printable_character'               => true,
        'pow_to_exponentiation'                 => true,
        'random_api_migration'                  => true,
        'use_arrow_functions'                   => true,

        // Class structure
        'class_attributes_separation' => [
            'elements' => [
                'const'    => 'one',
                'method'   => 'one',
                'property' => 'one',
            ],
        ],
        'no_unneeded_final_method' => true,
        'ordered_class_elements'   => [
            'order' => [
                'use_trait',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public_static',
                'property_protected_static',
                'property_private_static',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public_static',
                'method_protected_static',
                'method_private_static',
                'method_public',
                'method_protected',
                'method_private',
            ],
        ],
        'ordered_interfaces' => true,

        // Control structures and statements
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'no_superfluous_elseif'                  => true,
        'simplified_if_return'                   => true,
        'simplified_null_return'                 => true,

        // Comments and strings
        'explicit_string_variable'          => true,
        'multiline_comment_opening_closing' => true,

        // PHPUnit
        'php_unit_construct'                     => true,
        'php_unit_dedicate_assert'               => ['target' => 'newest'],
        'php_unit_expectation'                   => ['target' => 'newest'],
        'php_unit_mock'                          => ['target' => 'newest'],
        'php_unit_mock_short_will_return'        => true,
        'php_unit_set_up_tear_down_visibility'   => true,
        'php_unit_strict'                        => true,
        'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
    ],
);
