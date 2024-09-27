<?php

declare(strict_types=1);

namespace DI\HKT\TypeParameters;

interface TypeParametersInterface extends \Stringable
{
    /**
     * @return array<TypeParameterInterface>
     */
    public function getTypeParameters() : array;

    public function hasTypeParameter(TypeParameterInterface $typeParameter) : bool;

    public function getHash() : string;

    public function hasTypeParameters() : bool;
}
