<?php

namespace Bdf\Prime\Indexer\Exception;

use BadMethodCallException;

/**
 * Call a scope which is not declared
 */
class ScopeNotFoundException extends BadMethodCallException implements PrimeIndexerException
{
    /**
     * @var class-string
     */
    private $entity;

    /**
     * @var string
     */
    private $scope;

    /**
     * ScopeNotFoundException constructor.
     * @param class-string $entity
     * @param string $scope
     */
    public function __construct(string $entity, string $scope)
    {
        parent::__construct(sprintf('The scope "%s" cannot be found for the entity "%s"', $scope, $entity));

        $this->entity = $entity;
        $this->scope = $scope;
    }

    /**
     * The requested entity class name
     *
     * @return class-string
     */
    public function entity(): string
    {
        return $this->entity;
    }

    /**
     * The scope name
     *
     * @return string
     */
    public function scope(): string
    {
        return $this->scope;
    }
}
