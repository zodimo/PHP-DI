<?php

declare(strict_types=1);

namespace DI\HKT\Definition\Source;

use DI\HKT\Container\TypeParameters\TypeParametersInterface;
use DI\HKT\Definition\Definition;
use DI\HKT\Definition\ExtendsPreviousDefinition;
use DI\NotFoundException;

/**
 * Manages a chain of other definition sources.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 * @author Jaco Labuschagne <zodimo@gmail.com>
 */
class SourceChain implements DefinitionSource, MutableDefinitionSource
{
    private ?MutableDefinitionSource $mutableSource;

    /**
     * @param list<DefinitionSource> $sources
     */
    public function __construct(
        private array $sources,
    ) {
        $this->mutableSource = null;
    }

    /**
     * @param int $startIndex Use this parameter to start looking from a specific
     *                        point in the source chain.
     */
    public function getDefinition(string $name, TypeParametersInterface $typeParameters, int $startIndex = 0) : Definition
    {
        $count = count($this->sources);
        for ($i = $startIndex; $i < $count; ++$i) {
            $source = $this->sources[$i];

            if ($source->hasDefinition($name, $typeParameters)) {
                $definition = $source->getDefinition($name, $typeParameters);

                if ($definition instanceof ExtendsPreviousDefinition) {
                    $this->resolveExtendedDefinition($definition, $i);
                }

                return $definition;
            }

        }

        $typeParametersAsString = (string) $typeParameters;
        throw new NotFoundException("No entry or class found for '$name' and parameters '$typeParametersAsString'");
    }

    public function getDefinitions() : array
    {
        return array_merge(...array_map(fn (DefinitionSource $source) => $source->getDefinitions(), $this->sources));
    }

    public function addDefinition(Definition $definition) : void
    {
        if (! $this->mutableSource) {
            throw new \LogicException("The container's definition source has not been initialized correctly");
        }

        $this->mutableSource->addDefinition($definition);
    }

    private function resolveExtendedDefinition(ExtendsPreviousDefinition $definition, int $currentIndex) : void
    {
        // Look in the next sources only (else infinite recursion, and we can only extend
        // entries defined in the previous definition files - a previous == next here because
        // the array was reversed ;) )
        $subDefinition = $this->getDefinition($definition->getName(), $definition->getTypeParameters(), $currentIndex + 1);

        $definition->setExtendedDefinition($subDefinition);
    }

    public function setMutableDefinitionSource(MutableDefinitionSource $mutableSource) : void
    {
        $this->mutableSource = $mutableSource;

        array_unshift($this->sources, $mutableSource);
    }

    public function hasDefinition(string $name, TypeParametersInterface $typeParameters) : bool
    {
        $definitions = $this->getDefinitions();
        $definitionMatches = array_filter($definitions, function (Definition $definition) use ($name, $typeParameters) {
            return $name === $definition->getName() && $definition->getTypeParameters()->equals($typeParameters);
        });

        return count($definitionMatches) > 0;

    }
}
