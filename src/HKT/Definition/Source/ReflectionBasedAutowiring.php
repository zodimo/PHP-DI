<?php

declare(strict_types=1);

namespace DI\HKT\Definition\Source;

use DI\HKT\Definition\ObjectDefinition;
use DI\HKT\Definition\ObjectDefinition\MethodInjection;
use DI\HKT\Definition\Reference;
use DI\HKT\TypeParameters\TypeParametersInterface;
use DI\NotFoundException;
use ReflectionNamedType;

/**
 * Reads DI class definitions using reflection.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 * @author Jaco Labuschagne <zodimo@gmail.com>
 */
class ReflectionBasedAutowiring implements DefinitionSource, Autowiring
{
    public function autowire(string $name, TypeParametersInterface $typeParameters, ?ObjectDefinition $definition = null) : ObjectDefinition
    {
        $className = $definition ? $definition->getClassName() : $name;

        if (!class_exists($className) && !interface_exists($className)) {
            if (null === $definition) {
                throw new NotFoundException("Could not autowire {$name}");
            }

            return $definition;
        }

        $definition = $definition ?: new ObjectDefinition($name, $typeParameters);

        // Constructor
        $class = new \ReflectionClass($className);
        $constructor = $class->getConstructor();
        if ($constructor && $constructor->isPublic()) {
            $constructorInjection = MethodInjection::constructor($typeParameters, $this->getParametersDefinition($constructor));
            $definition->completeConstructorInjection($constructorInjection);
        }

        return $definition;
    }

    public function getDefinition(string $name, TypeParametersInterface $typeParameters) : ObjectDefinition
    {
        return $this->autowire($name, $typeParameters);
    }

    /**
     * Autowiring cannot guess all existing definitions.
     */
    public function getDefinitions() : array
    {
        return [];
    }

    /**
     * Read the type-hinting from the parameters of the function.
     */
    private function getParametersDefinition(\ReflectionFunctionAbstract $constructor) : array
    {
        $parameters = [];

        foreach ($constructor->getParameters() as $index => $parameter) {
            // Skip optional parameters
            if ($parameter->isOptional()) {
                continue;
            }

            $parameterType = $parameter->getType();
            if (!$parameterType) {
                // No type
                continue;
            }
            if (!$parameterType instanceof ReflectionNamedType) {
                // Union types are not supported
                continue;
            }
            if ($parameterType->isBuiltin()) {
                // Primitive types are not supported
                continue;
            }

            $parameters[$index] = new Reference($parameterType->getName());
        }

        return $parameters;
    }
}
