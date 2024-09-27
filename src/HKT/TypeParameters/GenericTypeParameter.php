<?php

declare(strict_types=1);

namespace DI\HKT\TypeParameters;

class GenericTypeParameter implements TypeParameterInterface
{
    private string $type;

    private function __construct(string $type)
    {
        $this->type = $type;
    }

    public static function create(string $typeAsString) : TypeParameterInterface
    {
        return new self($typeAsString);
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function equals(TypeParameterInterface $typeParameter) : bool
    {
        return $typeParameter->getType() === $this->getType();
    }
}
