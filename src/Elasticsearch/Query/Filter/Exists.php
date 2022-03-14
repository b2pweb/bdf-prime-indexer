<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammarInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\CompilableExpressionInterface;

/**
 * Returns documents that have at least one non-null value in the original field
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/query-dsl-exists-query.html
 */
final class Exists implements CompilableExpressionInterface
{
    private string $field;

    /**
     * Exists constructor.
     *
     * @param string $field
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
        return ['exists' => ['field' => $this->field]];
    }
}
