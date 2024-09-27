<?php

declare(strict_types=1);

namespace DI\HKT\TypeParameters;

class GenericTypeParameters implements TypeParametersInterface
{
    private array $types;

    private function __construct(array $types)
    {
        $this->types = $types;
    }

    /**
     * Type parameters are positional.
     * @param array<TypeParameterInterface> $typeParameters
     */
    public static function create(array $typeParameters) : TypeParametersInterface
    {
        $m = new self([]);
        foreach ($typeParameters as $typeParameter) {
            $m->addTypeParameter($typeParameter);
        }

        return $m;
    }

    public static function createEmpty() : TypeParametersInterface
    {
        return new self([]);
    }

    private function addTypeParameter(TypeParameterInterface $typeParameter) : void
    {
        $this->types[] = $typeParameter;
    }

    public function equals(TypeParametersInterface $other) : bool
    {
        /**
         * compare all types in sequence.
         */
        $thisTypePamaters = $this->getTypeParameters();
        $otherTypeParameters = $other->getTypeParameters();

        if (count($thisTypePamaters) !== count($otherTypeParameters)) {
            return false;
        }
        foreach ($thisTypePamaters as $index => $thisTypePamater) {
            if (!$thisTypePamater->equals($otherTypeParameters[$index])) {
                return false;
            }

        }

        return true;

    }

    public function getTypeParameters() : array
    {
        return $this->types;
    }

    public function hasTypeParameter(TypeParameterInterface $typeParameter) : bool
    {
        foreach ($this->getTypeParameters() as $thisTypePamater) {
            if ($thisTypePamater->equals($typeParameter)) {
                return true;
            }

        }

        return false;
    }

    public function getHash() : string
    {
        $comabinedHash = array_map(function (TypeParameterInterface $typeParameter) {
            return hash('sha256', $typeParameter->getType());
        }, $this->getTypeParameters());

        return hash('sha256', implode('.', $comabinedHash));
    }

    public function __toString() : string
    {
        $output = array_map(fn (TypeParameterInterface $typeParameter) => $typeParameter->getType(), $this->getTypeParameters());

        return implode(',', $output);

    }

    public function hasTypeParameters() : bool
    {
        return count($this->types) > 0;
    }
}
