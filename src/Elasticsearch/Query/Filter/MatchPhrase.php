<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammarInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\CompilableExpressionInterface;

/**
 * The match_phrase query analyzes the text and creates a phrase query out of the analyzed text
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/query-dsl-match-query.html#query-dsl-match-query-phrase
 */
final class MatchPhrase implements CompilableExpressionInterface
{
    private string $field;
    private string $search;

    /**
     * MatchPhrase constructor.
     *
     * @param string $field The field to search on
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
        return ['match_phrase' => [$this->field => $this->search]];
    }
}
