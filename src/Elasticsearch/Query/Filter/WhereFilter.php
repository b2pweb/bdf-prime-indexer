<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammarInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\CompilableExpressionInterface;

/**
 * Wrap ElasticsearchQuery::where() filters
 *
 * The expression will be compiled by the grammar
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
     * @param mixed $value
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
    public function compile(ElasticsearchGrammarInterface $grammar): array
    {
        return $grammar->operator($this->column, $this->operator, $this->value);
    }
}
