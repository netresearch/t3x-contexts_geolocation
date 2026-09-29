#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# SPDX-FileCopyrightText: Netresearch DTT GmbH

#
# TYPO3 extension test runner script
#
# Usage: ./Build/Scripts/runTests.sh [options] [suite]
#
# Runs the suites with the `php` found on PATH and the dependencies in
# .Build/ (installed with `composer install` if missing). It starts no
# containers; the PHP x TYPO3 matrix runs in CI (.github/workflows/ci.yml).
#
# Options:
#   -x            Enable xdebug for debugging
#   -v            Verbose output (PHPStan and PHP-CS-Fixer)
#   -h            Show this help
#
# Suites:
#   unit          Run unit tests (default)
#   functional    Run functional tests
#   lint          Run linting (PHPStan, PHP-CS-Fixer)
#   cgl           Run PHP-CS-Fixer (check only)
#   cglfix        Run PHP-CS-Fixer (fix)
#   phpstan       Run PHPStan static analysis
#   all           Run all test suites
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../.." && pwd)"

# Default values
XDEBUG=""
VERBOSE=""
SUITE="unit"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

print_help() {
    echo "TYPO3 Extension Test Runner"
    echo ""
    echo "Usage: $0 [options] [suite]"
    echo ""
    echo "Runs with the php on PATH; it starts no containers."
    echo ""
    echo "Options:"
    echo "  -x            Enable xdebug for debugging"
    echo "  -v            Verbose output (PHPStan and PHP-CS-Fixer)"
    echo "  -h            Show this help"
    echo ""
    echo "Suites:"
    echo "  unit          Run unit tests (default)"
    echo "  functional    Run functional tests"
    echo "  lint          Run all linting tools"
    echo "  cgl           Run PHP-CS-Fixer (check only)"
    echo "  cglfix        Run PHP-CS-Fixer (fix)"
    echo "  phpstan       Run PHPStan static analysis"
    echo "  all           Run all test suites"
    echo ""
}

# Parse command line arguments
while getopts "xvh" opt; do
    case ${opt} in
        x)
            XDEBUG="-dxdebug.mode=debug -dxdebug.start_with_request=yes"
            ;;
        v)
            VERBOSE="--verbose"
            ;;
        h)
            print_help
            exit 0
            ;;
        \?)
            print_help
            exit 1
            ;;
    esac
done

shift $((OPTIND - 1))
SUITE="${1:-unit}"

cd "${ROOT_DIR}"

# Ensure dependencies are installed
if [[ ! -d .Build/vendor ]]; then
    echo -e "${YELLOW}Installing dependencies...${NC}"
    composer install --prefer-dist --no-progress
fi

echo -e "${GREEN}Running suite: ${SUITE}${NC}"
echo -e "PHP: $(php -r 'echo PHP_VERSION;')"
echo ""

# Set database credentials for functional tests (DDEV or CI environment)
setup_database_env() {
    if [[ -z "${typo3DatabaseDriver:-}" ]]; then
        # Check if running in DDEV
        if [[ -n "${DDEV_PROJECT:-}" ]] || [[ -f /.dockerenv && -n "${DDEV_HOSTNAME:-}" ]]; then
            export typo3DatabaseDriver="pdo_mysql"
            export typo3DatabaseHost="db"
            export typo3DatabaseUsername="db"
            export typo3DatabasePassword="db"
            export typo3DatabaseName="db"
        # Check for GitHub Actions or other CI
        elif [[ -n "${CI:-}" ]]; then
            export typo3DatabaseDriver="pdo_sqlite"
        fi
    fi
}

case ${SUITE} in
    unit)
        echo -e "${GREEN}>>> Running Unit Tests${NC}"
        php ${XDEBUG} .Build/bin/phpunit -c Build/phpunit/UnitTests.xml
        ;;
    functional)
        echo -e "${GREEN}>>> Running Functional Tests${NC}"
        setup_database_env
        php ${XDEBUG} .Build/bin/phpunit -c Build/phpunit/FunctionalTests.xml
        ;;
    lint)
        echo -e "${GREEN}>>> Running PHPStan${NC}"
        .Build/bin/phpstan analyse -c Build/phpstan.neon ${VERBOSE}
        echo ""
        echo -e "${GREEN}>>> Running PHP-CS-Fixer (check)${NC}"
        .Build/bin/php-cs-fixer fix --dry-run --diff ${VERBOSE}
        ;;
    cgl)
        echo -e "${GREEN}>>> Running PHP-CS-Fixer (check)${NC}"
        .Build/bin/php-cs-fixer fix --dry-run --diff ${VERBOSE}
        ;;
    cglfix)
        echo -e "${GREEN}>>> Running PHP-CS-Fixer (fix)${NC}"
        .Build/bin/php-cs-fixer fix ${VERBOSE}
        ;;
    phpstan)
        echo -e "${GREEN}>>> Running PHPStan${NC}"
        .Build/bin/phpstan analyse -c Build/phpstan.neon ${VERBOSE}
        ;;
    all)
        echo -e "${GREEN}>>> Running All Suites${NC}"
        echo ""
        FAILED=""

        echo -e "${GREEN}>>> 1/4 PHPStan${NC}"
        .Build/bin/phpstan analyse -c Build/phpstan.neon || FAILED="${FAILED} phpstan"
        echo ""

        echo -e "${GREEN}>>> 2/4 PHP-CS-Fixer${NC}"
        .Build/bin/php-cs-fixer fix --dry-run --diff || FAILED="${FAILED} cgl"
        echo ""

        echo -e "${GREEN}>>> 3/4 Unit Tests${NC}"
        php .Build/bin/phpunit -c Build/phpunit/UnitTests.xml || FAILED="${FAILED} unit"
        echo ""

        echo -e "${GREEN}>>> 4/4 Functional Tests${NC}"
        setup_database_env
        php .Build/bin/phpunit -c Build/phpunit/FunctionalTests.xml || FAILED="${FAILED} functional"
        echo ""

        if [[ -n "${FAILED}" ]]; then
            echo -e "${RED}Failed suites:${FAILED}${NC}"
            exit 1
        fi
        echo -e "${GREEN}All suites completed${NC}"
        ;;
    *)
        echo -e "${RED}Unknown suite: ${SUITE}${NC}"
        print_help
        exit 1
        ;;
esac

echo ""
echo -e "${GREEN}Done!${NC}"
