<?php

declare(strict_types=1);

namespace DI\HKT\Definition\Source;

use DI\HKT\Definition\Exception\InvalidDefinition;
use DI\HKT\Definition\ObjectDefinition;
use DI\HKT\TypeParameters\TypeParametersInterface;

/**
 * Implementation used when autowiring is completely disabled.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class NoAutowiring implements Autowiring
{
    public function autowire(string $name, TypeParametersInterface $typeParameters, ?ObjectDefinition $definition = null) : ObjectDefinition
    {
        throw new InvalidDefinition(sprintf(
            'Cannot autowire entry "%s" because autowiring is disabled',
            $name
        ));
    }
}
