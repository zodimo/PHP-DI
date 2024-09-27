<?php

declare(strict_types=1);

namespace DI\HKT\Definition\Source;

use DI\HKT\Container\TypeParameters\TypeParametersInterface;
use DI\HKT\Definition\Exception\InvalidDefinition;
use DI\HKT\Definition\ObjectDefinition;

/**
 * Implementation used when autowiring is completely disabled.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class NoAutowiring implements Autowiring
{
    public function autowireUnsafe(string $name, TypeParametersInterface $typeParameters, ?ObjectDefinition $definition = null) : ObjectDefinition
    {
        throw new InvalidDefinition(sprintf(
            'Cannot autowire entry "%s" because autowiring is disabled',
            $name
        ));
    }
}
