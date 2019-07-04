<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammarInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\CompilableExpressionInterface;

/**
 * Class WhereFilter
 */
final class WhereFilter implements CompilableExpressionInterface
{
    /**
     * @var string
     */
    private $column;

    /**
     * @var string
     */
    private $operator;

    /**
     * @var string
     */
    private $value;


    /**
     * WhereFilter constructor.
     *
     * @param string $column
     * @param string $operator
     * @param string $value
     */
    public function __construct($column, $operator, $value)
    {
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        $this->column = $column;
        $this->operator = $operator ?: '=';
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(ElasticsearchGrammarInterface $grammar)
    {
        return $grammar->operator($this->column, $this->operator, $this->value);
    }
}