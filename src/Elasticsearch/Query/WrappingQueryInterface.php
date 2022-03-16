<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query;

/**
 * Wrap other query to modify its behavior
 */
interface WrappingQueryInterface extends CompilableExpressionInterface
{
    /**
     * Set the inner query
     *
     * @param CompilableExpressionInterface|null $innerQuery
     *
     * @return static
     */
    public function wrap(?CompilableExpressionInterface $innerQuery = null): WrappingQueryInterface;
}
