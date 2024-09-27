<?php

declare(strict_types=1);

namespace DI\HKT\Definition\Resolver;

use DI\HKT\Container\HigherKindedContainerInterface;
use DI\HKT\Definition\ArrayDefinition;
use DI\HKT\Definition\DecoratorDefinition;
use DI\HKT\Definition\Definition;
use DI\HKT\Definition\EnvironmentVariableDefinition;
use DI\HKT\Definition\Exception\InvalidDefinition;
use DI\HKT\Definition\FactoryDefinition;
use DI\HKT\Definition\InstanceDefinition;
use DI\HKT\Definition\ObjectDefinition;
use DI\HKT\Definition\SelfResolvingDefinition;
use DI\HKT\Proxy\ProxyFactory;

/**
 * Dispatches to more specific resolvers.
 *
 * Dynamic dispatch pattern.
 *
 * @since 5.0
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ResolverDispatcher implements DefinitionResolver
{
    private ?ArrayResolver $arrayResolver = null;
    private ?FactoryResolver $factoryResolver = null;
    private ?DecoratorResolver $decoratorResolver = null;
    private ?ObjectCreator $objectResolver = null;
    private ?InstanceInjector $instanceResolver = null;
    private ?EnvironmentVariableResolver $envVariableResolver = null;

    public function __construct(
        private HigherKindedContainerInterface $container,
        private ProxyFactory $proxyFactory,
    ) {
    }

    /**
     * Resolve a definition to a value.
     *
     * @param Definition $definition Object that defines how the value should be obtained.
     * @param array      $parameters Optional parameters to use to build the entry.
     *
     * @return mixed Value obtained from the definition.
     * @throws InvalidDefinition If the definition cannot be resolved.
     */
    public function resolve(Definition $definition, array $parameters = []) : mixed
    {
        // Special case, tested early for speed
        if ($definition instanceof SelfResolvingDefinition) {
            return $definition->resolve($this->container);
        }

        $definitionResolver = $this->getDefinitionResolver($definition);

        return $definitionResolver->resolve($definition, $parameters);
    }

    public function isResolvable(Definition $definition, array $parameters = []) : bool
    {
        // Special case, tested early for speed
        if ($definition instanceof SelfResolvingDefinition) {
            return $definition->isResolvable($this->container);
        }

        $definitionResolver = $this->getDefinitionResolver($definition);

        return $definitionResolver->isResolvable($definition, $parameters);
    }

    /**
     * Returns a resolver capable of handling the given definition.
     *
     * @throws \RuntimeException No definition resolver was found for this type of definition.
     */
    private function getDefinitionResolver(Definition $definition) : DefinitionResolver
    {
        switch (true) {
            case $definition instanceof ObjectDefinition:
                if (! $this->objectResolver) {
                    $this->objectResolver = new ObjectCreator($this, $this->proxyFactory);
                }

                return $this->objectResolver;
            case $definition instanceof DecoratorDefinition:
                if (! $this->decoratorResolver) {
                    $this->decoratorResolver = new DecoratorResolver($this->container, $this);
                }

                return $this->decoratorResolver;
            case $definition instanceof FactoryDefinition:
                if (! $this->factoryResolver) {
                    $this->factoryResolver = new FactoryResolver($this->container, $this);
                }

                return $this->factoryResolver;
            case $definition instanceof ArrayDefinition:
                if (! $this->arrayResolver) {
                    $this->arrayResolver = new ArrayResolver($this);
                }

                return $this->arrayResolver;
            case $definition instanceof EnvironmentVariableDefinition:
                if (! $this->envVariableResolver) {
                    $this->envVariableResolver = new EnvironmentVariableResolver($this);
                }

                return $this->envVariableResolver;
            case $definition instanceof InstanceDefinition:
                if (! $this->instanceResolver) {
                    $this->instanceResolver = new InstanceInjector($this, $this->proxyFactory);
                }

                return $this->instanceResolver;
            default:
                throw new \RuntimeException('No definition resolver was configured for definition of type ' . $definition::class);
        }
    }
}
