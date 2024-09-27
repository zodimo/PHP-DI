<?php

declare(strict_types=1);

namespace DI\HKT\Definition;

use DI\HKT\HigherKindedContainerInterface;

/**
 * Describes a definition that can resolve itself.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface SelfResolvingDefinition
{
    /**
     * Resolve the definition and return the resulting value.
     */
    public function resolve(HigherKindedContainerInterface $container) : mixed;

    /**
     * Check if a definition can be resolved.
     */
    public function isResolvable(HigherKindedContainerInterface $container) : bool;
}
