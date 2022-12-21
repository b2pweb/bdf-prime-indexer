<?php

namespace Bdf\Prime\Indexer\Exception;

/**
 * Call a scope which is not declared
 */
class ScopeNotFoundException extends InvalidQueryException
{
    /**
     * @var class-string
     */
    private string $entity;

    /**
     * @var string
     */
    private string $scope;

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
