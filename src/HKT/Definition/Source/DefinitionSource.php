<?php

declare(strict_types=1);

namespace DI\HKT\Definition\Source;

use DI\HKT\Container\TypeParameters\TypeParametersInterface;
use DI\HKT\Definition\Definition;
use DI\HKT\Definition\Exception\InvalidDefinition;
use DI\NotFoundException;

/**
 * Source of definitions for entries of the container.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 * @author Jaco Labuschagne <zodimo@gmail.com>
 */
interface DefinitionSource
{
    /**
     * Returns the DI definition for the entry name.
     *
     * @throws InvalidDefinition An invalid definition was found.
     * @throws NotFoundException A definition was not found.
     */
    public function getDefinition(string $name, TypeParametersInterface $typeParameters) : Definition;

    /**
     * @return array<string,Definition> Definitions indexed by their name.
     */
    public function getDefinitions() : array;

    public function hasDefinition(string $name, TypeParametersInterface $typeParameters) : bool;
}
