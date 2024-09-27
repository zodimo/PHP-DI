<?php

declare(strict_types=1);

namespace DI\HKT\Definition\Source;

use DI\HKT\Definition\Definition;
use DI\HKT\TypeParameters\TypeParametersInterface;
use DI\NotFoundException;

/**
 * Reads DI definitions from a PHP array.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 * @author Jaco Labuschagne <zodimo@gmail.com>
 */
class DefinitionArray implements DefinitionSource, MutableDefinitionSource
{
    public const WILDCARD = '*';
    /**
     * Matches anything except "\".
     */
    private const WILDCARD_PATTERN = '([^\\\\]+)';

    /** DI definitions in a PHP array. */
    private array $definitions;

    /** Cache of wildcard definitions. */
    private ?array $wildcardDefinitions = null;

    private DefinitionNormalizer $normalizer;

    public function __construct(array $definitions = [], ?Autowiring $autowiring = null)
    {
        if (isset($definitions[0])) {
            throw new \Exception('The PHP-DI definition is not indexed by an entry name in the definition array');
        }

        $this->definitions = $definitions;

        $this->normalizer = new DefinitionNormalizer($autowiring ?: new NoAutowiring());
    }

    /**
     * @param array $definitions DI definitions in a PHP array indexed by the definition name.
     */
    public function addDefinitions(array $definitions) : void
    {
        if (isset($definitions[0])) {
            throw new \Exception('The PHP-DI definition is not indexed by an entry name in the definition array');
        }

        // The newly added data prevails
        // "for keys that exist in both arrays, the elements from the left-hand array will be used"
        $this->definitions = $definitions + $this->definitions;

        // Clear cache
        $this->wildcardDefinitions = null;
    }

    public function addDefinition(Definition $definition) : void
    {
        $this->definitions[$definition->getName()] = $definition;

        // Clear cache
        $this->wildcardDefinitions = null;
    }

    private function hasDefinition(string $name, TypeParametersInterface $typeParameters) : bool
    {
        if ($typeParameters->hasTypeParameters()) {
            return isset($this->definitions[$name][$typeParameters->getHash()]);
        }

        return isset($this->definitions[$name]);

    }

    private function getDefinitionUnsafe(string $name, TypeParametersInterface $typeParameters) : mixed
    {

        if ($typeParameters->hasTypeParameters()) {
            $definition = $this->definitions[$name][$typeParameters->getHash()] ?? null;
        } else {
            $definition = $this->definitions[$name] ?? null;
        }

        if (null === $definition) {
            throw new \RuntimeException('BUG!! getDefinitionUnsafe: I told you it was unsafe!');
        }

        return $definition;
    }

    public function getDefinition(string $name, TypeParametersInterface $typeParameters) : Definition
    {
        // Look for the definition by name
        if ($this->hasDefinition($name, $typeParameters)) {
            $definition = $this->getDefinitionUnsafe($name, $typeParameters);

            return $this->normalizer->normalizeRootDefinition($definition, $name);
        }

        // Build the cache of wildcard definitions
        if ($this->wildcardDefinitions === null) {
            $this->wildcardDefinitions = [];
            foreach ($this->definitions as $key => $definition) {
                if (str_contains($key, self::WILDCARD)) {
                    $this->wildcardDefinitions[$key] = $definition;
                }
            }
        }

        // Look in wildcards definitions
        foreach ($this->wildcardDefinitions as $key => $definition) {
            // Turn the pattern into a regex
            $key = preg_quote($key, '#');
            $key = '#^' . str_replace('\\' . self::WILDCARD, self::WILDCARD_PATTERN, $key) . '#';
            if (preg_match($key, $name, $matches) === 1) {
                array_shift($matches);

                return $this->normalizer->normalizeRootDefinition($definition, $name, $matches);
            }
        }
        /**
         * @todo improve this error
         */
        throw new NotFoundException('Definition not found');
    }

    public function getDefinitions() : array
    {
        // Return all definitions except wildcard definitions
        $definitions = [];
        foreach ($this->definitions as $key => $definition) {
            if (! str_contains($key, self::WILDCARD)) {
                $definitions[$key] = $definition;
            }
        }

        return $definitions;
    }
}
