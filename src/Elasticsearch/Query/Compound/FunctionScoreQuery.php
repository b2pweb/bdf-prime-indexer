<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Compound;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammarInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\CompilableExpressionInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\WrappingQueryInterface;

/**
 * The function_score allows you to modify the score of documents that are retrieved by a query.
 * This can be useful if, for example, a score function is computationally expensive
 * and it is sufficient to compute the score on a filtered set of documents.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/query-dsl-function-score-query.html
 */
final class FunctionScoreQuery implements WrappingQueryInterface
{
    /**
     * @var CompilableExpressionInterface
     */
    private $query;

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var array
     */
    private $functions = [];

    /**
     * Add a new function for compute the score
     *
     * <code>
     * $query = new FunctionScoreQuery();
     *
     * $query->addFunction('field_value_factor', [
     *     'field' => 'population',
     *     'factor' => 1,
     *     'modifier' => 'log1p'
     * ]);
     *
     * $query->addFunction('random_score', ['seed' => 123]);
     * </code>
     *
     * @param string $type The function type (ex: "field_value_factor", "weight"...)
     * @param array $parameters The fonction parameters. Depends of the function type
     * @param array $filter The query for filter the entries that are used by the score function
     * @param float $weight The weight of the function score over all functions
     *
     * @return $this
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/query-dsl-function-score-query.html#score-functions For list of function types
     */
    public function addFunction(string $type, array $parameters, array $filter = [], float $weight = null): FunctionScoreQuery
    {
        $function = [$type => $parameters];

        if ($filter) {
            $function['filter'] = $filter;
        }

        if ($weight !== null) {
            $function['weight'] = $weight;
        }

        $this->functions[] = $function;

        return $this;
    }

    /**
     * Retrict the score boost by the maxBoost value.
     *
     * @param float $value
     *
     * @return $this
     */
    public function maxBoost(float $value): FunctionScoreQuery
    {
        $this->options['max_boost'] = $value;

        return $this;
    }

    /**
     * To exclude documents that do not meet a certain score threshold the min_score parameter can be set to the desired score threshold.
     *
     * @param float $value
     *
     * @return $this
     */
    public function minScore(float $value): FunctionScoreQuery
    {
        $this->options['min_score'] = $value;

        return $this;
    }

    /**
     * Specify how the computed scores are combined
     *
     * - multiply : scores are multiplied (default)
     * - sum      : scores are summed
     * - avg      : scores are averaged
     * - first    : the first function that has a matching filter is applied
     * - max      : maximum score is used
     * - min      : minimum score is used
     *
     * @param string $mode The score mode
     *
     * @return $this
     */
    public function scoreMode(string $mode): FunctionScoreQuery
    {
        $this->options['score_mode'] = $mode;

        return $this;
    }

    /**
     * Specify how the functions scores are combined with the query score
     *
     * - multiply : query score and function score is multiplied (default)
     * - replace  : only function score is used, the query score is ignored
     * - sum      : query score and function score are added
     * - avg      : average
     * - max      : max of query score and function score
     * - min      : min of query score and function score
     *
     * @param string $mode The boost mode
     *
     * @return $this
     */
    public function boostMode(string $mode): FunctionScoreQuery
    {
        $this->options['boost_mode'] = $mode;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function wrap(CompilableExpressionInterface $innerQuery = null): WrappingQueryInterface
    {
        $this->query = $innerQuery;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(ElasticsearchGrammarInterface $grammar): array
    {
        $compiled = $this->options;

        if (count($this->functions) === 1 && count($this->functions[0]) === 1) {
            $compiled += $this->functions[0];
        } else {
            $compiled['functions'] = $this->functions;
        }

        if ($this->query) {
            $compiled['query'] = $this->query->compile($grammar);
        }

        return ['function_score' => $compiled];
    }
}
