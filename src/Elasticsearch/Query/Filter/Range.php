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

    public function gt($value)
    {
        $this->parameters['gt'] = $value;

        return $this;
    }

    public function gte($value)
    {
        $this->parameters['gte'] = $value;

        return $this;
    }

    public function lte($value)
    {
        $this->parameters['lte'] = $value;

        return $this;
    }

    public function lt($value)
    {
        $this->parameters['lt'] = $value;

        return $this;
    }

    public function boost($value)
    {
        $this->parameters['boost'] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(ElasticsearchGrammarInterface $grammar)
    {
        return ['range' => [$this->field => array_map([$grammar, 'escape'], $this->parameters)]];
    }
}
