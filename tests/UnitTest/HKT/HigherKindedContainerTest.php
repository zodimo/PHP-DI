<?php

declare(strict_types=1);

namespace DI\Test\UnitTest\HKT;

use DI\HKT\HigherKindedContainer;
use PHPUnit\Framework\TestCase;

/**
 * Test class for DI\Container.
 */
class HigherKindedContainerTest extends TestCase
{
    public function testCanBeBuiltWithoutParameters() : void
    {
        self::assertInstanceOf(HigherKindedContainer::class, new HigherKindedContainer); // Should not be an error
    }

    public function testCanBeBuiltWithDefinitionArray() : void
    {
        $container = new HigherKindedContainer([
            'foo' => 'bar',
        ]);
        self::assertInstanceOf(HigherKindedContainer::class, $container);
        self::assertEquals('bar', $container->get('foo'));
    }
}
