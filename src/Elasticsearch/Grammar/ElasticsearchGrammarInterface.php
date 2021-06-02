<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Grammar;

use Bdf\Prime\Indexer\Elasticsearch\Exception\GrammarException;
use Bdf\Prime\Indexer\Elasticsearch\Query\CompilableExpressionInterface;

/**
 * Expression grammar for elasticsearch
 */
interface ElasticsearchGrammarInterface
{
    /**
     * Escape meta characters from the value
     *
     * @param string $value
     *
     * @return string
     */
    public function escape($value);

    /**
     * Compile a simple operator
     *
     * @param string $field
     * @param string $operator
     * @param mixed $value
     *
     * @return array
     *
     * @throws GrammarException When cannot generate a valid expression
     */
    public function operator($field, $operator, $value);

    /**
     * Negates the expression filter
     *
     * @param array|CompilableExpressionInterface $expression
     *
     * @return array
     */
    public function not($expression);

    /**
     * Combine expression with "OR" logic operator
     *
     * @param array|CompilableExpressionInterface[] $expressions
     *
     * @return array
     */
    public function or(array $expressions);

    /**
     * Transform SQL LIKE search expression to elasticsearch Wildcard expression
     *
     * Hello% => Hello*
     * H_lo   => H?lo
     *
     * @param string $query The LIKE search query
     * @param bool $escape Does the metacharacters should be escaped ?
     *
     * @return string
     */
    public function likeToWildcard($query, bool $escape = true);
}
