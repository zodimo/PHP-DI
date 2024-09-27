<?php

declare(strict_types=1);

namespace DI\Test\UnitTest;

use DI\Container;
use DI\HKT\HigherKindedContainer;
use DI\NotFoundException;
use DI\Test\UnitTest\Fixtures\PassByReferenceDependency;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Test class for Container.
 *
 * @covers \DI\Container
 */
class HigherKindedContainerGetTest extends TestCase
{
    public function testSetGet() : void
    {
        $container = new HigherKindedContainer;
        $dummy = new stdClass();
        $container->set('key', $dummy);
        $this->assertSame($dummy, $container->get('key'));
    }

    public function testGetNotFound() : void
    {
        $this->expectException(NotFoundException::class);
        $container = new HigherKindedContainer;
        $container->get('key');
    }

    public function testClosureIsResolved() : void
    {
        $closure = function () : string {
            return 'hello';
        };
        $container = new HigherKindedContainer;
        $container->set('key', $closure);
        $this->assertEquals('hello', $container->get('key'));
    }

    // public function testGetWithClassName()
    // {
    //     $container = new Container;
    //     $this->assertInstanceOf('stdClass', $container->get('stdClass'));
    // }

    // public function testGetResolvesEntryOnce()
    // {
    //     $container = new Container;
    //     $this->assertSame($container->get('stdClass'), $container->get('stdClass'));
    // }

    // /**
    //  * Tests a class can be initialized with a parameter passed by reference.
    //  */
    // public function testPassByReferenceParameter()
    // {
    //     $container = new Container;
    //     $object = $container->get(PassByReferenceDependency::class);
    //     $this->assertInstanceOf(PassByReferenceDependency::class, $object);
    // }
}
