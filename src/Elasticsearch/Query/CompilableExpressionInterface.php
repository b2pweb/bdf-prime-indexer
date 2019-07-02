<?php


namespace Bdf\Prime\Indexer\Elasticsearch\Query;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammarInterface;

/**
 * Interface CompilableExpressionInterface
 * @package Bdf\Prime\Indexer\Elasticsearch\Query
 */
interface CompilableExpressionInterface
{
    /**
     * Compile the expression
     *
     * @param ElasticsearchGrammarInterface $grammar
     *
     * @return array
     */
    public function compile(ElasticsearchGrammarInterface $grammar);
}
