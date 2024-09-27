<?php

declare(strict_types=1);

namespace DI\Test\UnitTest\Fixtures;

use DI\HKT\Container\HigherKindedContainerInterface;
use DI\HKT\Definition\Source\DefinitionSource;
use DI\HKT\Proxy\ProxyFactory;

/**
 * Fake container class that exposes all constructor parameters.
 *
 * Used to test the ContainerBuilder.
 */
class FakeHigherKindedContainer
{
    /**
     * @var DefinitionSource
     */
    public $definitionSource;

    /**
     * @var ProxyFactory
     */
    public $proxyFactory;

    /**
     * @var HigherKindedContainerInterface
     */
    public $wrapperContainer;

    public function __construct(
        DefinitionSource $definitionSource,
        ProxyFactory $proxyFactory,
        ?HigherKindedContainerInterface $wrapperContainer = null,
    ) {
        $this->definitionSource = $definitionSource;
        $this->proxyFactory = $proxyFactory;
        $this->wrapperContainer = $wrapperContainer;
    }
}
