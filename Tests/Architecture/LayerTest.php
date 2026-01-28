<?php

/**
 * This file is part of the package netresearch/contexts-geolocation.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\ContextsGeolocation\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

/**
 * Architecture tests to enforce layer boundaries.
 *
 * @see https://github.com/carlosas/phpat
 */
final class LayerTest
{
    /**
     * Context type classes should extend the contexts extension AbstractContext.
     */
    public function testContextTypesExtendAbstract(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Netresearch\ContextsGeolocation\Context\Type'))
            ->shouldExtend()
            ->classes(
                Selector::classname('Netresearch\Contexts\Context\AbstractContext')
            )
            ->because('All context types should extend AbstractContext from the contexts extension');
    }

    /**
     * DTO classes should be readonly.
     */
    public function testDtosAreReadonly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Netresearch\ContextsGeolocation\Dto'))
            ->shouldBeReadonly()
            ->because('DTOs should be immutable');
    }

    /**
     * Exception classes should be final.
     */
    public function testExceptionsAreFinal(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Netresearch\ContextsGeolocation\Exception'))
            ->shouldBeFinal()
            ->because('Exception classes should be final');
    }

    /**
     * Adapter classes should implement an interface.
     */
    public function testAdaptersImplementInterface(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Netresearch\ContextsGeolocation\Adapter'))
            ->excluding(
                Selector::classname('/.*Interface$/')
            )
            ->shouldImplement()
            ->classes(
                Selector::classname('Netresearch\ContextsGeolocation\Adapter\AdapterInterface')
            )
            ->because('All adapters should implement AdapterInterface');
    }
}
