<?php

declare(strict_types=1);

namespace DI\HKT\Definition;

use DI\HKT\Container\HigherKindedContainerInterface;
use DI\HKT\Container\TypeParameters\TypeParametersInterface;

/**
 * Represents a reference to another entry.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 * @author Jaco Labuschagne <zodimo@gmail.com>
 */
class Reference implements Definition, SelfResolvingDefinition
{
    public function __construct(
        private string $name,
        private TypeParametersInterface $typeParameters,
    ) {
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function resolve(HigherKindedContainerInterface $container) : mixed
    {
        return $container->get($this->getName(), $this->getTypeParameters());
    }

    public function isResolvable(HigherKindedContainerInterface $container) : bool
    {
        return $container->has($this->getName(), $this->getTypeParameters());
    }

    public function replaceNestedDefinitions(callable $replacer) : void
    {
        // no nested definitions
    }

    public function __toString() : string
    {
        $entryDescription = "{$this->name}[{$this->typeParameters}]";

        return sprintf(
            'get(%s)',
            $entryDescription
        );
    }

    public function getTypeParameters() : TypeParametersInterface
    {
        return $this->typeParameters;

    }

    public function setTypeParameters(TypeParametersInterface $typeParameters) : void
    {
        $this->typeParameters = $typeParameters;
    }
}
