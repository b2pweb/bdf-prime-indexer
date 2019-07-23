<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammarInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\CompilableExpressionInterface;

/**
 * Find documents where the field specified does is missing or contains only null values.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/query-dsl-missing-query.html
 */
final class Missing implements CompilableExpressionInterface
{
    /**
     * @var string
     */
    private $field;

    /**
     * Missing constructor.
     *
     * @param string $field The field name
     */
    public function __construct(string $field)
    {
        $this->field = $field;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(ElasticsearchGrammarInterface $grammar): array
    {
        return ['missing' => ['field' => $this->field]];
    }
}
