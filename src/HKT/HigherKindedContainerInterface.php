<?php

declare(strict_types=1);

namespace DI\HKT;

use DI\HKT\TypeParameters\TypeParametersInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

interface HigherKindedContainerInterface
{
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string        $id         identifier of the entry to look for
     * @param TypeParametersInterface> $typeParameters type dependencies as strings
     *
     * @return mixed entry
     *
     * @throws NotFoundExceptionInterface  no entry was found for **this** identifier
     * @throws ContainerExceptionInterface error while retrieving the entry
     */
    public function get(string $id, ?TypeParametersInterface $typeParameters);

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string        $id         identifier of the entry to look for
     * @param TypeParametersInterface $typeParameters type dependencies as strings
     */
    public function has(string $id, ?TypeParametersInterface $typeParameters) : bool;
}
