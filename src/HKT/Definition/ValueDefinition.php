<?php

declare(strict_types=1);

namespace DI\HKT\Definition;

use DI\HKT\HigherKindedContainerInterface;
use DI\HKT\TypeParameters\GenericTypeParameters;
use DI\HKT\TypeParameters\TypeParametersInterface;

/**
 * Definition of a value for dependency injection.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ValueDefinition implements Definition, SelfResolvingDefinition
{
    /**
     * Entry name.
     */
    private string $name = '';

    private TypeParametersInterface $typeParameters;

    public function __construct(
        private mixed $value,
    ) {
        $this->typeParameters = GenericTypeParameters::createEmpty();
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function getValue() : mixed
    {
        return $this->value;
    }

    public function resolve(HigherKindedContainerInterface $container) : mixed
    {
        return $this->getValue();
    }

    public function isResolvable(HigherKindedContainerInterface $container) : bool
    {
        return true;
    }

    public function replaceNestedDefinitions(callable $replacer) : void
    {
        // no nested definitions
    }

    public function __toString() : string
    {
        return sprintf('Value (%s)', var_export($this->value, true));
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
