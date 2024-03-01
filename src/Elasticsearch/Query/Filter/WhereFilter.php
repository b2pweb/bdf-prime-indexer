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
    private string $column;
    private string $operator;

    /**
     * @var mixed
     */
    private $value;


    /**
     * WhereFilter constructor.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     */
    public function __construct(string $column, string $operator, $value)
    {
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

    /**
     * Get the field name to filter
     */
    public function column(): string
    {
        return $this->column;
    }

    /**
     * Get the used operator
     */
    public function operator(): string
    {
        return $this->operator;
    }
}
