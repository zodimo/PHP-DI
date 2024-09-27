<?php

declare(strict_types=1);

namespace DI\HKT;

use DI\DependencyException;
use DI\HKT\Container\HigherKindedContainerInterface;
use DI\HKT\Container\TypeParameters\TypeParametersInterface;
use DI\HKT\Definition\Definition;
use DI\HKT\Definition\FactoryDefinition;
use DI\HKT\Definition\Helper\DefinitionHelper;
use DI\HKT\Definition\Resolver\DefinitionResolver;
use DI\HKT\Definition\Resolver\ResolverDispatcher;
use DI\HKT\Definition\Source\DefinitionArray;
use DI\HKT\Definition\Source\MutableDefinitionSource;
use DI\HKT\Definition\Source\ReflectionBasedAutowiring;
use DI\HKT\Definition\Source\SourceChain;
use DI\HKT\Definition\ValueDefinition;
use DI\HKT\Proxy\ProxyFactory;
use DI\HKT\TypeParameters\GenericTypeParameters;

class HigherKindedContainer implements HigherKindedContainerInterface
{
    /**
     * Map of entries that are already resolved.
     * @var array<string,mixed>|array<string,array<string,mixed>>
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

    private function getResolvedEntryUnsafe(string $id, TypeParametersInterface $typeParameters) : mixed
    {
        if ($typeParameters->hasTypeParameters()) {
            $entry = $this->resolvedEntries[$id][$typeParameters->getHash()] ?? null;
        } else {
            $entry = $this->resolvedEntries[$id] ?? null;
        }
        if (null === $entry) {
            throw new \RuntimeException('BUG!! getResolvedEntryUnsafe: I told you it was unsafe!');
        }

        return $entry;
    }

    private function hasResolvedEntry(string $id, TypeParametersInterface $typeParameters) : bool
    {
        if ($typeParameters->hasTypeParameters()) {
            return isset($this->resolvedEntries[$id][$typeParameters->getHash()]);
        }

        return isset($this->resolvedEntries[$id]);
    }

    private function setResolvedEntry(Definition $definition, mixed $value) : void
    {
        $typeParameters = $definition->getTypeParameters();
        if ($typeParameters->hasTypeParameters()) {
            $this->resolvedEntries[$definition->getName()][$typeParameters->getHash()] = $value;
        } else {
            $this->resolvedEntries[$definition->getName()] = $value;
        }
    }

    private function unSetResolvedEntry(Definition $definition) : void
    {
        $typeParameters = $definition->getTypeParameters();
        if ($typeParameters->hasTypeParameters()) {
            unset($this->resolvedEntries[$definition->getName()][$typeParameters->getHash()]);
        } else {
            unset($this->resolvedEntries[$definition->getName()]);
        }
    }

    public function get(string $id, ?TypeParametersInterface $typeParameters = null) : mixed
    {
        if (null === $typeParameters) {
            $typeParameters = GenericTypeParameters::createEmpty();
        }
        // If the entry is already resolved we return it
        if ($this->hasResolvedEntry($id, $typeParameters)) {
            return $this->getResolvedEntryUnsafe($id, $typeParameters);
        }

        $definition = $this->getDefinitionUnsafe($id, $typeParameters);

        $value = $this->resolveDefinition($definition);

        $this->setResolvedEntry($definition, $value);

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

    private function getDefinitionUnsafe(string $name, TypeParametersInterface $typeParameters) : Definition
    {
        // Local cache that avoids fetching the same definition twice
        if (!$this->hasFetchedDefinition($name, $typeParameters)) {
            $definition = $this->definitionSource->getDefinition($name, $typeParameters);
            $this->setFetchedDefinition($definition);

            return $definition;
        }

        return $this->getFetchedDefinitionUnsafe($name, $typeParameters);
    }

    public function hasDefinition(string $name, TypeParametersInterface $typeParameters) : bool
    {

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

    /**
     * Define an object or a value in the container.
     *
     * @param string $name Entry name
     * @param mixed|DefinitionHelper $value Value, use definition helpers to define objects
     */
    public function set(string $name, mixed $value, ?TypeParametersInterface $typeParameters = null) : void
    {
        if (null === $typeParameters) {
            $typeParameters = GenericTypeParameters::createEmpty();
        }

        if ($value instanceof DefinitionHelper) {
            $value = $value->getDefinition($name);
        } elseif ($value instanceof \Closure) {
            $value = new FactoryDefinition($name, $value);
        }

        if ($value instanceof ValueDefinition) {
            $this->resolvedEntries[$name] = $value->getValue();
        } elseif ($value instanceof Definition) {
            $value->setName($name);
            $this->setDefinition($name, $value);
        } else {
            $this->resolvedEntries[$name] = $value;
        }
    }

    protected function setDefinition(string $name, Definition $definition) : void
    {
        if ($name !== $definition->getName()) {
            throw new \RuntimeException('BUG: setDefinition, names do not match!');
        }
        // Clear existing entry if it exists
        if ($this->hasResolvedEntry($name, $definition->getTypeParameters())) {
            unset($this->resolvedEntries[$name]);
            $this->unSetResolvedEntry($definition);
        }
        $this->fetchedDefinitions = []; // Completely clear this local cache

        $this->definitionSource->addDefinition($definition);
    }
}
