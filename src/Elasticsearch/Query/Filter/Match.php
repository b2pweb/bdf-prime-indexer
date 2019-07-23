<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammarInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\CompilableExpressionInterface;

/**
 * The standard query for performing full text queries, including fuzzy matching and phrase or proximity queries.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/query-dsl-match-query.html
 */
final class Match implements CompilableExpressionInterface
{
    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $search;


    /**
     * MatchPhrase constructor.
     *
     * @param string $field The field name to search on
     * @param string $search The search term
     */
    public function __construct(string $field, string $search)
    {
        $this->field = $field;
        $this->search = $search;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(ElasticsearchGrammarInterface $grammar): array
    {
        return ['match' => [$this->field => $this->search]];
    }
}
