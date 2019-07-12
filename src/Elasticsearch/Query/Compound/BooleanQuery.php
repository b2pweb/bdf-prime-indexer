<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Compound;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammarInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\CompilableExpressionInterface;

/**
 * A compound query with boolean combinations.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/query-dsl-bool-query.html
 */
final class BooleanQuery implements CompilableExpressionInterface
{
    const COMPOSITE_AND = 'AND';
    const COMPOSITE_OR = 'OR';
    const COMPOSITE_FILTER = 'FILTER';
    const COMPOSITE_SHOULD = 'SHOULD';
    const COMPOSITE_MUST = 'MUST';
    const COMPOSITE_MUST_NOT = 'MUST_NOT';

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var array
     */
    private $must = [];

    /**
     * @var array
     */
    private $mustNot = [];

    /**
     * @var array
     */
    private $filter = [];

    /**
     * @var array
     */
    private $should = [];


    /**
     * The clause (query) must appear in matching documents and will contribute to the score.
     *
     * @param array|CompilableExpressionInterface $query
     *
     * @return $this
     */
    public function must($query): BooleanQuery
    {
        $this->must[] = $query;

        return $this;
    }

    /**
     * The clause (query) must appear in matching documents.
     * However unlike must the score of the query will be ignored.
     *
     * @param array|CompilableExpressionInterface $query
     *
     * @return $this
     */
    public function filter($query): BooleanQuery
    {
        $this->filter[] = $query;

        return $this;
    }

    /**
     * The clause (query) must not appear in the matching documents.
     *
     * @param array|CompilableExpressionInterface $query
     *
     * @return $this
     */
    public function mustNot($query): BooleanQuery
    {
        $this->mustNot[] = $query;

        return $this;
    }

    /**
     * The clause (query) should appear in the matching document.
     * In a boolean query with no must or filter clauses, one or more should clauses must match a document.
     * The minimum number of should clauses to match can be set using the minimum_should_match parameter.
     *
     * @param array|CompilableExpressionInterface $query
     *
     * @return $this
     */
    public function should($query): BooleanQuery
    {
        $this->should[] = $query;

        if (!isset($this->options['minimum_should_match'])) {
            $this->minimumShouldMatch(1);
        }

        return $this;
    }

    /**
     * Minimum required should query that match
     *
     * @param integer $value
     *
     * @return $this
     */
    public function minimumShouldMatch($value): BooleanQuery
    {
        $this->options['minimum_should_match'] = $value;

        return $this;
    }

    /**
     * Boost the score value
     *
     * @param float $value
     *
     * @return $this
     */
    public function boost($value): BooleanQuery
    {
        $this->options['boost'] = $value;

        return $this;
    }

    /**
     * Check if the query is an "OR" query
     * An OR query is a query that contains only should clauses
     *
     * @return bool
     */
    public function isOrQuery(): bool
    {
        return $this->empty() ||
            (!empty($this->should) && empty($this->filter) && empty($this->must) && empty($this->mustNot));
    }

    /**
     * Partitionate the query for create an "OR" query
     * After this call, the current query is ensured that contains only should clauses
     *
     * <code>
     * // (name = 'Paris' AND population > 10000) OR (name = 'Lyon' AND population > 50000)
     * $query
     *     ->filter(new Match('name', 'Paris'))
     *     ->filter((new Range('population'))->gt(10000))
     *     ->or()
     *     ->filter(new Match('name', 'Lyon'))
     *     ->filter((new Range('population'))->gt(50000))
     * ;
     * </code>
     *
     * @return BooleanQuery
     */
    public function or(): BooleanQuery
    {
        if ($this->isOrQuery()) {
            return $this;
        }

        // Contains only one filter : set the filter into the "should" clause
        if ($this->count() === 1 && count($this->filter) === 1) {
            $this->should($this->filter[0]);
            $this->filter = [];

            return $this;
        }

        // Partitionate the query : set the current clauses into the should clause and create a new boolean query
        $filter = clone $this;
        $newFilter = new BooleanQuery();

        $this->filter = [];
        $this->must = [];
        $this->mustNot = [];
        $this->should = [$filter, $newFilter];
        $this->minimumShouldMatch(1);

        return $newFilter;
    }

    /**
     * Get a valid boolean query for perform "AND" combination
     * On an "OR" query, will returns the last should clause
     *
     * @return BooleanQuery
     */
    public function and(): BooleanQuery
    {
        if ($this->empty() || !$this->isOrQuery()) {
            return $this;
        }

        $lastShould = end($this->should);

        if ($lastShould instanceof BooleanQuery) {
            return $lastShould;
        }

        $lastShould = (new BooleanQuery())->filter($lastShould);

        $this->should[key($this->should)] = $lastShould;

        return $lastShould;
    }

    /**
     * Check if the boolean query has no filters
     *
     * @return bool
     */
    public function empty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * Count all available clauses
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->filter) + count($this->must) + count($this->mustNot) + count($this->should);
    }

    /**
     * {@inheritdoc}
     */
    public function compile(ElasticsearchGrammarInterface $grammar): array
    {
        $compiled = $this->options;

        if (empty($this->mustNot) && $this->count() === 1) {
            $filter = $this->should[0] ?? $this->filter[0] ?? $this->must[0];

            if ($filter instanceof BooleanQuery) {
                return $filter->compile($grammar);
            }
        }

        if (!empty($this->filter)) {
            $compiled['filter'] = $this->compileFilterType($grammar, $this->filter);
        }

        if (!empty($this->must)) {
            $compiled['must'] = $this->compileFilterType($grammar, $this->must);
        }

        if (!empty($this->mustNot)) {
            $compiled['must_not'] = $this->compileFilterType($grammar, $this->mustNot);
        }

        if (!empty($this->should)) {
            $compiled['should'] = $this->compileFilterType($grammar, $this->should);
        }

        $compiled = $this->optimizeNot($compiled);
        $compiled = $this->optimizeSingleNestedFilter($compiled);

        return ['bool' => $compiled];
    }

    /**
     * Compile a single filter clause
     *
     * @param ElasticsearchGrammarInterface $grammar
     * @param array $filter
     *
     * @return array
     */
    private function compileFilterType(ElasticsearchGrammarInterface $grammar, array $filter): array
    {
        $compiled = [];

        foreach ($filter as $expression) {
            if ($expression instanceof CompilableExpressionInterface) {
                $expression = $expression->compile($grammar);
            }

            $compiled[] = $expression;
        }

        return $compiled;
    }

    /**
     * Optimize nested "not" filter. Do not optimize "must" because it contribute to score (unlike filter and must_not)
     *
     * @param array $compiled
     *
     * @return array
     */
    private function optimizeNot(array $compiled): array
    {
        if (empty($compiled['filter'])) {
            return $compiled;
        }

        $optimized = false;

        foreach ($compiled['filter'] as $key => $expression) {
            if (!self::isNotFilter($expression)) {
                continue;
            }

            if (!empty($compiled['must_not'])) {
                $compiled['must_not'] = array_merge($compiled['must_not'], $expression['bool']['must_not']);
            } else {
                $compiled['must_not'] = $expression['bool']['must_not'];
            }

            unset($compiled['filter'][$key]);
            $optimized = true;
        }

        // Not filter found : reindexing filter
        if ($optimized) {
            $compiled['filter'] = array_values($compiled['filter']);
        }

        return $compiled;
    }

    /**
     * Optimize a filter with a single nested boolean query with same filter
     *
     * @param array $compiled
     *
     * @return array
     */
    private function optimizeSingleNestedFilter(array $compiled): array
    {
        $types = ['should', 'filter', 'must'];

        foreach ($types as $type) {
            if (empty($compiled[$type])) {
                continue;
            }

            $expressions = $compiled[$type];

            if (count($expressions) !== 1 || !isset($expressions[0]['bool'])) {
                continue;
            }

            $expression = $expressions[0]['bool'];

            // Check if the nested expression contains only the current filter
            foreach ($types as $toCheck) {
                $empty = empty($expression[$toCheck]);

                if ($toCheck === $type) {
                    if ($empty) {
                        continue 2;
                    }
                } elseif (!$empty) {
                    continue 2;
                }
            }

            // Check if options match
            foreach ($this->options as $option => $value) {
                if (!isset($expression[$option]) || $expression[$option] != $value) {
                    continue 2;
                }

                unset($expression[$option]);
            }

            // Check if the nested expression contains only the current clause type
            if (array_keys($expression) !== [$type]) {
                continue;
            }

            $compiled[$type] = $expression[$type];
        }

        return $compiled;
    }

    /**
     * Check if the given expression is a "not" boolean query (i.e. contains only "must_not" clause)
     *
     * @param array $filter
     *
     * @return bool
     */
    private static function isNotFilter(array $filter): bool
    {
        return !empty($filter['bool']) && count($filter['bool']) === 1 && !empty($filter['bool']['must_not']);
    }
}
