<?php


namespace Bdf\Prime\Indexer\Elasticsearch\Query;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammarInterface;

/**
 * Expression for elasticsearch query which can be compiled into an array
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
    public function compile(ElasticsearchGrammarInterface $grammar): array;
}
