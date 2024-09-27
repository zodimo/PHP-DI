<?php

declare(strict_types=1);

namespace DI\HKT\Definition;

use DI\HKT\Factory\RequestedEntry;
use DI\HKT\TypeParameters\TypeParametersInterface;

/**
 * Definition.
 *
 * @internal This interface is internal to PHP-DI and may change between minor versions.
 *
 * @author Jaco Labuschagne <zodimo@gmail.com>
 */
interface Definition extends RequestedEntry, \Stringable
{
    /**
     * Returns the name of the entry in the container.
     */
    public function getName() : string;

    /**
     * Set the name of the entry in the container.
     */
    public function setName(string $name) : void;

    public function getTypeParameters() : TypeParametersInterface;

    public function setTypeParameters(TypeParametersInterface $typeParameters) : void;

    /**
     * Apply a callable that replaces the definitions nested in this definition.
     */
    public function replaceNestedDefinitions(callable $replacer) : void;

    /**
     * Definitions can be cast to string for debugging information.
     */
    public function __toString() : string;
}
