<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Compound;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammarInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\CompilableExpressionInterface;

/**
 * Wrap another query for search on a nested field
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/7.17/query-dsl-nested-query.html
 */
class Nested implements CompilableExpressionInterface
{
    public const SCORE_MODE_AVG = 'avg';
    public const SCORE_MODE_MAX = 'max';
    public const SCORE_MODE_MIN = 'min';
    public const SCORE_MODE_NONE = 'none';
    public const SCORE_MODE_SUM = 'sum';

    private string $path;
    private CompilableExpressionInterface $query;
    private array $options = [];

    /**
     * @param string $path Path to the nested object you wish to search
     * @param CompilableExpressionInterface $query Query you wish to run on nested objects in the path. If an object matches the search, the nested query returns the root parent document.
     */
    public function __construct(string $path, CompilableExpressionInterface $query)
    {
        $this->path = $path;
        $this->query = $query;
    }

    /**
     * Indicates how scores for matching child objects affect the root parent document’s relevance score.
     *
     * Valid values are:
     * - avg: (Default) Use the mean relevance score of all matching child objects.
     * - max: Uses the highest relevance score of all matching child objects.
     * - min: Uses the lowest relevance score of all matching child objects.
     * - none: Do not use the relevance scores of matching child objects. The query assigns parent documents a score of 0.
     * - sum: Add together the relevance scores of all matching child objects
     *
     * @param Nested::SCORE_MODE_* $mode The score mode. Use one of the Nested::SCORE_MODE_* constants
     *
     * @return $this
     */
    public function scoreMode(string $mode): self
    {
        $this->options['score_mode'] = $mode;

        return $this;
    }

    /**
     * Indicates whether to ignore an unmapped path and not return any documents instead of an error. Defaults to false
     *
     * If false, Elasticsearch returns an error if the path is an unmapped field.
     * You can use this parameter to query multiple indices that may not contain the field path.
     *
     * @param bool $flag
     *
     * @return $this
     */
    public function ignoreUnmapped(bool $flag = true): self
    {
        $this->options['ignore_unmapped'] = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(ElasticsearchGrammarInterface $grammar): array
    {
        return [
            'nested' => [
                'path' => $this->path,
                'query' => $this->query->compile($grammar),
            ] + $this->options
        ];
    }
}
