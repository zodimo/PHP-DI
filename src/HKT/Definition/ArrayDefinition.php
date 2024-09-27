<?php

declare(strict_types=1);

namespace DI\HKT\Definition;

use DI\HKT\TypeParameters\GenericTypeParameters;
use DI\HKT\TypeParameters\TypeParametersInterface;

/**
 * Definition of an array containing values or references.
 *
 * @since 5.0
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ArrayDefinition implements Definition
{
    private TypeParametersInterface $typeParameters;
    /** Entry name. */
    private string $name = '';

    public function __construct(
        private array $values,
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

    public function getValues() : array
    {
        return $this->values;
    }

    public function replaceNestedDefinitions(callable $replacer) : void
    {
        $this->values = array_map($replacer, $this->values);
    }

    public function __toString() : string
    {
        $str = '[' . \PHP_EOL;

        foreach ($this->values as $key => $value) {
            if (is_string($key)) {
                $key = "'" . $key . "'";
            }

            $str .= '    ' . $key . ' => ';

            if ($value instanceof Definition) {
                $str .= str_replace(\PHP_EOL, \PHP_EOL . '    ', (string) $value);
            } else {
                $str .= var_export($value, true);
            }

            $str .= ',' . \PHP_EOL;
        }

        return $str . ']';
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
