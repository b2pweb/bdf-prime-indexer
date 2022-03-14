<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammarInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\CompilableExpressionInterface;

/**
 * Find documents where the field specified contains values (dates, numbers, or strings) in the range specified.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/query-dsl-range-query.html
 */
final class Range implements CompilableExpressionInterface
{
    private string $field;
    private array $parameters = [];

    /**
     * Range constructor.
     *
     * @param string $field
     */
    public function __construct(string $field)
    {
        $this->field = $field;
    }

    /**
     * Greater-than
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function gt($value): Range
    {
        $this->parameters['gt'] = $value;

        return $this;
    }

    /**
     * Greater-than or equal to
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function gte($value): Range
    {
        $this->parameters['gte'] = $value;

        return $this;
    }

    /**
     * Less-than or equal to
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function lte($value): Range
    {
        $this->parameters['lte'] = $value;

        return $this;
    }

    /**
     * Less-than
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function lt($value): Range
    {
        $this->parameters['lt'] = $value;

        return $this;
    }

    /**
     * Sets the boost value of the query, defaults to 1.0
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function boost($value): Range
    {
        $this->parameters['boost'] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(ElasticsearchGrammarInterface $grammar): array
    {
        return ['range' => [$this->field => array_map([$grammar, 'escape'], $this->parameters)]];
    }
}
