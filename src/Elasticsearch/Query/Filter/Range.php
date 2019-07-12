<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;


use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammarInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\CompilableExpressionInterface;

/**
 * Class Range
 */
final class Range implements CompilableExpressionInterface
{
    /**
     * @var string
     */
    private $field;

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * Range constructor.
     *
     * @param string $field
     */
    public function __construct(string $field)
    {
        $this->field = $field;
    }

    public function gt($value): Range
    {
        $this->parameters['gt'] = $value;

        return $this;
    }

    public function gte($value): Range
    {
        $this->parameters['gte'] = $value;

        return $this;
    }

    public function lte($value): Range
    {
        $this->parameters['lte'] = $value;

        return $this;
    }

    public function lt($value): Range
    {
        $this->parameters['lt'] = $value;

        return $this;
    }

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
