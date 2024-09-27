<?php

declare(strict_types=1);

namespace DI\HKT\TypeParameters;

interface TypeParameterInterface
{
    public function getType() : string;

    public function equals(self $typeParameter) : bool;
}
