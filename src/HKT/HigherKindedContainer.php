<?php

declare(strict_types=1);

namespace DI\HKT;

use DI\DependencyException;
use DI\HKT\Definition\Definition;
use DI\HKT\Definition\Resolver\DefinitionResolver;
use DI\HKT\Definition\Resolver\ResolverDispatcher;
use DI\HKT\Definition\Source\DefinitionArray;
use DI\HKT\Definition\Source\MutableDefinitionSource;
use DI\HKT\Definition\Source\ReflectionBasedAutowiring;
use DI\HKT\Definition\Source\SourceChain;
use DI\HKT\Proxy\ProxyFactory;
use DI\HKT\TypeParameters\GenericTypeParameters;
use DI\HKT\TypeParameters\TypeParametersInterface;

class HigherKindedContainer implements HigherKindedContainerInterface
{
    /**
     * Map of entries that are already resolved.
     */
    protected array $resolvedEntries = [];

    private MutableDefinitionSource $definitionSource;

    private DefinitionResolver $definitionResolver;

    /**
     * Map of definitions that are already fetched (local cache).
     *
     * @var array<string,array<string,Definition>>
     */
    private array $fetchedDefinitions = [];

    /**
     * Array of entries being resolved. Used to avoid circular dependencies and infinite loops.
     * @var array<string,array<string,bool>>
     */
    protected array $entriesBeingResolved = [];

    /**
     * Container that wraps this container. If none, points to $this.
     */
    protected HigherKindedContainerInterface $delegateContainer;

    protected ProxyFactory $proxyFactory;

    public function __construct(
        array|MutableDefinitionSource $definitions = [],
        ?ProxyFactory $proxyFactory = null,
        ?HigherKindedContainerInterface $wrapperContainer = null,
    ) {
        if (is_array($definitions)) {
            $this->definitionSource = $this->createDefaultDefinitionSource($definitions);
        } else {
            $this->definitionSource = $definitions;
        }
        $this->delegateContainer = $wrapperContainer ?: $this;
        $this->proxyFactory = $proxyFactory ?: new ProxyFactory;
        $this->definitionResolver = new ResolverDispatcher($this->delegateContainer, $this->proxyFactory);
    }

    public static function create() : self
    {
        return new self();
    }

    private function getResolvedEntry(string $id, ?TypeParametersInterface $typeParameters)
    {

    }

    public function get(string $id, ?TypeParametersInterface $typeParameters = null) : mixed
    {
        if (null === $typeParameters) {
            $typeParameters = GenericTypeParameters::createEmpty();
        }
        // If the entry is already resolved we return it
        if (isset($this->resolvedEntries[$id]) || array_key_exists($id, $this->resolvedEntries)) {
            return $this->resolvedEntries[$id];
        }

        $definition = $this->getDefinition($id, $typeParameters);

        $value = $this->resolveDefinition($definition);

        $this->resolvedEntries[$id] = $value;

        return $value;
    }

    /**
     * @psalm-assert-if-true !null $this->getFetchedDefinitionUnsafe(string $name, TypeParametersInterface $typeParameters)
     * @psalm-assert-if-false null $this->getFetchedDefinitionUnsafe(string $name, TypeParametersInterface $typeParameters)
     */
    private function hasFetchedDefinition(string $name, TypeParametersInterface $typeParameters) : bool
    {
        return isset($this->fetchedDefinitions[$name][$typeParameters->getHash()]);
    }

    private function getFetchedDefinitionUnsafe(string $name, TypeParametersInterface $typeParameters) : Definition
    {
        $definition = $this->fetchedDefinitions[$name][$typeParameters->getHash()] ?? null;
        if (null === $definition) {
            throw new \RuntimeException('BUG!! getFetchedDefinitionUnsafe: I told you it was unsafe!');
        }

        return $definition;

    }

    private function setFetchedDefinition(Definition $definition) : void
    {
        $typeParameters = $definition->getTypeParameters();
        $this->fetchedDefinitions[$definition->getName()][$typeParameters->getHash()] = $definition;

    }

    private function getDefinition(string $name, TypeParametersInterface $typeParameters) : Definition
    {
        // Local cache that avoids fetching the same definition twice
        if (!$this->hasFetchedDefinition($name, $typeParameters)) {
            $definition = $this->definitionSource->getDefinition($name, $typeParameters);
            $this->setFetchedDefinition($definition);

            return $definition;
        }

        return $this->getFetchedDefinitionUnsafe($name, $typeParameters);
    }

    public function has(string $id, ?TypeParametersInterface $typeParameters) : bool
    {
        return false;
    }

    private function isEntryBeingResolved(string $entryName, ?TypeParametersInterface $typeParameters) : bool
    {
        if (null !== $typeParameters) {
            return $this->entriesBeingResolved[$entryName][$typeParameters->getHash()] ?? false;
        }

        return $this->entriesBeingResolved[$entryName] ?? false;
    }

    public function setEntryBeingResolved(string $entryName, ?TypeParametersInterface $typeParameters) : void
    {
        if (null !== $typeParameters) {
            $this->entriesBeingResolved[$entryName][$typeParameters->getHash()] = true;
        } else {
            $this->entriesBeingResolved[$entryName] = true;
        }

    }

    public function unSetEntryBeingResolved(string $entryName, ?TypeParametersInterface $typeParameters) : void
    {
        if (null !== $typeParameters) {
            unset($this->entriesBeingResolved[$entryName][$typeParameters->getHash()]);
        } else {
            unset($this->entriesBeingResolved[$entryName]);
        }

    }

    /**
     * Resolves a definition.
     *
     * Checks for circular dependencies while resolving the definition.
     *
     * @throws DependencyException Error while resolving the entry.
     */
    private function resolveDefinition(Definition $definition, array $parameters = []) : mixed
    {
        $entryName = $definition->getName();
        $typeParameters = $definition->getTypeParameters();

        // Check if we are already getting this entry -> circular dependency
        if ($this->isEntryBeingResolved($entryName, $typeParameters)) {
            /**
             * @todo iclude type paramterers in the error
             */
            $entryList = implode(' -> ', [...array_keys($this->entriesBeingResolved), $entryName]);
            throw new DependencyException("Circular dependency detected while trying to resolve entry '$entryName': Dependencies: " . $entryList);
        }
        $this->setEntryBeingResolved($entryName, $typeParameters);

        // Resolve the definition
        try {
            $value = $this->definitionResolver->resolve($definition, $parameters);
        } finally {
            $this->unSetEntryBeingResolved($entryName, $typeParameters);
        }

        return $value;
    }

    private function createDefaultDefinitionSource(array $definitions) : SourceChain
    {
        $autowiring = new ReflectionBasedAutowiring();
        $source = new SourceChain([$autowiring]);
        $source->setMutableDefinitionSource(new DefinitionArray($definitions, $autowiring));

        return $source;
    }
}
