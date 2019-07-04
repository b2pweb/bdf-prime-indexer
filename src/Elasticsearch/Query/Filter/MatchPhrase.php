<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammarInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\CompilableExpressionInterface;

/**
 * Class MatchPhrase
 */
final class MatchPhrase implements CompilableExpressionInterface
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
     * @param string $field
     * @param string $search
     */
    public function __construct($field, $search)
    {
        $this->field = $field;
        $this->search = $search;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(ElasticsearchGrammarInterface $grammar)
    {
        return ['match_phrase' => [$this->field => $this->search]];
    }
}