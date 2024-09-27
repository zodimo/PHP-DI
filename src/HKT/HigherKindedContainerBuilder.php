<?php

declare(strict_types=1);

namespace DI\HKT;

use DI\HKT\Container\HigherKindedContainerInterface;
use DI\HKT\Definition\Source\AttributeBasedAutowiring;
use DI\HKT\Definition\Source\DefinitionArray;
use DI\HKT\Definition\Source\DefinitionSource;
use DI\HKT\Definition\Source\NoAutowiring;
use DI\HKT\Definition\Source\ReflectionBasedAutowiring;
use DI\HKT\Definition\Source\SourceChain;
use DI\HKT\Proxy\ProxyFactory;

/**
 * Helper to create and configure a Container.
 *
 * With the default options, the container created is appropriate for the development environment.
 *
 * Example:
 *
 *     $builder = new ContainerBuilder();
 *     $container = $builder->build();
 *
 * @api
 *
 * @since  3.2
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 *
 * @psalm-template ContainerClass of HigherKindedContainer
 */
class HigherKindedContainerBuilder
{
    /**
     * Name of the container class, used to create the container.
     * @var class-string<HigherKindedContainer>
     * @psalm-var class-string<HigherKindedContainer>
     */
    private string $containerClass;

    private bool $useAutowiring = true;

    private bool $useAttributes = false;

    /**
     * If set, write the proxies to disk in this directory to improve performances.
     */
    private ?string $proxyDirectory = null;
    /**
     * If PHP-DI is wrapped in another container, this references the wrapper.
     */
    private ?HigherKindedContainerInterface $wrapperContainer = null;

    /**
     * @var DefinitionSource[]|string[]|array[]
     */
    private array $definitionSources = [];

    private ?string $compileToDirectory = null;

    private bool $sourceCache = false;

    /**
     * @param class-string<HigherKindedContainer> $containerClass Name of the container class, used to create the container.
     * @psalm-param class-string<HigherKindedContainer> $containerClass
     */
    public function __construct(string $containerClass = HigherKindedContainer::class)
    {
        $this->containerClass = $containerClass;
    }

    /**
     * Build and return a container.
     *
     * @return HigherKindedContainer
     * @psalm-return ContainerClass
     */
    public function build()
    {
        $sources = array_reverse($this->definitionSources);

        if ($this->useAttributes) {
            $autowiring = new AttributeBasedAutowiring;
            $sources[] = $autowiring;
        } elseif ($this->useAutowiring) {
            $autowiring = new ReflectionBasedAutowiring;
            $sources[] = $autowiring;
        } else {
            $autowiring = new NoAutowiring;
        }

        $sources = array_map(function ($definitions) use ($autowiring) {
            if (is_string($definitions)) {
                // File
                return new DefinitionFile($definitions, $autowiring);
            }
            if (is_array($definitions)) {
                return new DefinitionArray($definitions, $autowiring);
            }

            return $definitions;
        }, $sources);
        $source = new SourceChain($sources);

        // Mutable definition source
        $source->setMutableDefinitionSource(new DefinitionArray([], $autowiring));

        if ($this->sourceCache) {
            if (!SourceCache::isSupported()) {
                throw new \Exception('APCu is not enabled, PHP-DI cannot use it as a cache');
            }
            // Wrap the source with the cache decorator
            $source = new SourceCache($source, $this->sourceCacheNamespace);
        }

        $proxyFactory = new ProxyFactory($this->proxyDirectory);

        $this->locked = true;

        $containerClass = $this->containerClass;

        if ($this->compileToDirectory) {
            $compiler = new Compiler($proxyFactory);
            $compiledContainerFile = $compiler->compile(
                $source,
                $this->compileToDirectory,
                $containerClass,
                $this->containerParentClass,
                $this->useAutowiring
            );
            // Only load the file if it hasn't been already loaded
            // (the container can be created multiple times in the same process)
            if (!class_exists($containerClass, false)) {
                require $compiledContainerFile;
            }
        }

        return new $containerClass($source, $proxyFactory, $this->wrapperContainer);
    }
}
