<?php

declare(strict_types=1);

namespace DI\HKT\Definition\Source;

use DI\HKT\Container\TypeParameters\TypeParametersInterface;
use DI\HKT\Definition\Exception\InvalidDefinition;
use DI\HKT\Definition\ObjectDefinition;
use DI\NotFoundException;

/**
 * Source of definitions for entries of the container.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 * @author Jaco Labuschagne <zodimo@gm>
 */
interface Autowiring
{
    /**
     * Autowire the given definition.
     *
     * @throws InvalidDefinition An invalid definition was found.
     * @throws NotFoundException if no definition was found.
     */
    public function autowireUnsafe(string $name, TypeParametersInterface $typeParameters, ?ObjectDefinition $definition = null) : ObjectDefinition;
}
