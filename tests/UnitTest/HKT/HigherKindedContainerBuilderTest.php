<?php

declare(strict_types=1);

namespace DI\Test\UnitTest;

use DI\CompiledContainer;

use DI\HKT\Container\HigherKindedContainerInterface;
use DI\HKT\Definition\Source\DefinitionArray;
use DI\HKT\Definition\Source\SourceCache;
use DI\HKT\Definition\ValueDefinition;
use DI\HKT\HigherKindedContainerBuilder;
use DI\HKT\TypeParameters\GenericTypeParameters;
use DI\Test\IntegrationTest\BaseContainerTest;
use DI\Test\UnitTest\Fixtures\FakeContainer;
use DI\Test\UnitTest\Fixtures\FakeHigherKindedContainer;
use EasyMock\EasyMock;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DI\ContainerBuilder
 */
class HigherKindedContainerBuilderTest extends TestCase
{
    use EasyMock;

    private static function getProperty(object $object, string $propertyName)
    {
        /**
         * @psalm-suppress PossiblyInvalidFunctionCall
         */
        return (function (string $propertyName) {
            return $this->$propertyName;
        })->bindTo($object, $object)($propertyName);
    }

    public function testShould_configure_for_development_by_default()
    {
        // Make the ContainerBuilder use our fake class to catch constructor parameters
        $builder = new HigherKindedContainerBuilder(FakeHigherKindedContainer::class);
        /** @var FakeHigherKindedContainer $container */
        $container = $builder->build();

        // Not compiled
        $this->assertNotInstanceOf(CompiledContainer::class, $container);
        // Proxies evaluated in memory
        $this->assertNull(self::getProperty($container->proxyFactory, 'proxyDirectory'));
    }

    public function testShould_allow_to_configure_a_cache()
    {
        if (! SourceCache::isSupported()) {
            $this->markTestSkipped('APCu extension is required');

            return;
        }

        $builder = new HigherKindedContainerBuilder(FakeHigherKindedContainer::class);
        $builder->enableDefinitionCache();

        /** @var FakeContainer $container */
        $container = $builder->build();

        $this->assertInstanceOf(SourceCache::class, $container->definitionSource);
    }

    public function testShould_allow_to_configure_a_cache_with_a_namespace()
    {
        if (! SourceCache::isSupported()) {
            $this->markTestSkipped('APCu extension is required');

            return;
        }

        $namespace = 'staging';
        $builder = new HigherKindedContainerBuilder(FakeHigherKindedContainer::class);
        $builder->enableDefinitionCache($namespace);

        /** @var FakeContainer $container */
        $container = $builder->build();
        $source = $container->definitionSource;

        $this->assertInstanceOf(SourceCache::class, $source);
        $this->assertSame($source->getCacheKey('foo'), SourceCache::CACHE_KEY . $namespace . 'foo');
    }

    public function testThe_container_should_not_be_wrapped_by_default()
    {
        $builder = new HigherKindedContainerBuilder(FakeHigherKindedContainer::class);
        /** @var FakeHigherKindedContainer $container */
        $container = $builder->build();

        $this->assertNull($container->wrapperContainer);
    }

    public function testShould_allow_to_set_a_wrapper_container()
    {
        $otherContainer = $this->easyMock(HigherKindedContainerInterface::class);

        $builder = new HigherKindedContainerBuilder(FakeHigherKindedContainer::class);
        $builder->wrapContainer($otherContainer);

        /** @var FakeContainer $container */
        $container = $builder->build();

        $this->assertSame($otherContainer, $container->wrapperContainer);
    }

    public function testShould_allow_to_add_custom_definition_sources()
    {
        $builder = new HigherKindedContainerBuilder(FakeHigherKindedContainer::class);

        // Custom definition sources should be chained correctly
        $builder->addDefinitions(new DefinitionArray(['foo' => 'bar']), new DefinitionArray(['foofoo' => 'barbar']));

        /** @var FakeHigherKindedContainer $container */
        $container = $builder->build();

        // We should be able to get entries from our custom definition sources
        /** @var ValueDefinition $definition */
        $definition = $container->definitionSource->getDefinition('foo', GenericTypeParameters::createEmpty());
        $this->assertInstanceOf(ValueDefinition::class, $definition);
        $this->assertSame('bar', $definition->getValue());
        $definition = $container->definitionSource->getDefinition('foofoo', GenericTypeParameters::createEmpty());
        $this->assertInstanceOf(ValueDefinition::class, $definition);
        $this->assertSame('barbar', $definition->getValue());
    }

    public function testShould_chain_definition_sources_in_reverse_order()
    {
        $builder = new HigherKindedContainerBuilder(FakeHigherKindedContainer::class);

        $builder->addDefinitions(new DefinitionArray(['foo' => 'bar']), new DefinitionArray(['foo' => 'bim']));

        /** @var FakeHigherKindedContainer $container */
        $container = $builder->build();

        /** @var ValueDefinition $definition */
        $definition = $container->definitionSource->getDefinition('foo', GenericTypeParameters::createEmpty());
        $this->assertSame('bim', $definition->getValue());
    }

    public function testShould_allow_to_add_definitions_in_an_array()
    {
        $builder = new HigherKindedContainerBuilder(FakeHigherKindedContainer::class);

        // Custom definition sources should be chained correctly
        $builder->addDefinitions(['foo' => 'bar'], ['foofoo' => 'barbar']);

        /** @var FakeHigherKindedContainer $container */
        $container = $builder->build();

        /** @var ValueDefinition $definition */
        $definition = $container->definitionSource->getDefinition('foo', GenericTypeParameters::createEmpty());
        $this->assertInstanceOf(ValueDefinition::class, $definition);
        $this->assertSame('bar', $definition->getValue());
        $definition = $container->definitionSource->getDefinition('foofoo', GenericTypeParameters::createEmpty());
        $this->assertInstanceOf(ValueDefinition::class, $definition);
        $this->assertSame('barbar', $definition->getValue());
    }

    // public function testShould_allow_to_create_a_compiled_container()
    // {
    //     $builder = new HigherKindedContainerBuilder();
    //     $builder->enableCompilation(BaseContainerTest::COMPILATION_DIR);

    //     $this->assertInstanceOf(CompiledContainer::class, $builder->build());
    // }

    // /**
    //  * That allows to create several compiled containers in the same process.
    //  */
    // public function testShould_allow_to_customize_the_class_name_of_the_compiled_container()
    // {
    //     $builder = new ContainerBuilder();
    //     $className = 'Container' . uniqid();
    //     $builder->enableCompilation(BaseContainerTest::COMPILATION_DIR, $className);

    //     $this->assertInstanceOf(CompiledContainer::class, $builder->build());
    //     $this->assertInstanceOf($className, $builder->build());
    // }

    public function testShould_have_a_fluent_interface()
    {
        $builder = new HigherKindedContainerBuilder();

        $result = $builder->useAttributes(false);
        $this->assertSame($builder, $result);

        $result = $builder->useAttributes(true);
        $this->assertSame($builder, $result);

        $result = $builder->useAutowiring(false);
        $this->assertSame($builder, $result);

        $result = $builder->useAutowiring(true);
        $this->assertSame($builder, $result);

        $result = $builder->writeProxiesToFile(true, 'somedir');
        $this->assertSame($builder, $result);

        $result = $builder->writeProxiesToFile(false);
        $this->assertSame($builder, $result);

        // $result = $builder->enableCompilation('foo');
        // $this->assertSame($builder, $result);

        $result = $builder->enableDefinitionCache();
        $this->assertSame($builder, $result);

        $result = $builder->wrapContainer($this->easyMock(HigherKindedContainerInterface::class));
        $this->assertSame($builder, $result);
    }

    /**
     * Ensure the ContainerBuilder cannot be modified after the container has been built.
     */
    public function testShould_throw_if_modified_after_building_a_container()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('The ContainerBuilder cannot be modified after the container has been built');
        $builder = new HigherKindedContainerBuilder();
        $builder->build();

        $builder->addDefinitions([]);
    }

    public function testShould_create_proxies()
    {
        $builder = new HigherKindedContainerBuilder(FakeHigherKindedContainer::class);

        $builder->writeProxiesToFile(true, 'somedir');
        $container = $builder->build();

        $this->assertSame('somedir', self::getProperty($container->proxyFactory, 'proxyDirectory'));
    }

    public function testShould_not_create_proxies()
    {
        $builder = new HigherKindedContainerBuilder(FakeHigherKindedContainer::class);

        $builder->writeProxiesToFile(false, 'somedir');
        $container = $builder->build();

        $this->assertNull(self::getProperty($container->proxyFactory, 'proxyDirectory'));
    }
}
