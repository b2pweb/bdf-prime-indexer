<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammarInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\CompilableExpressionInterface;

/**
 * A query that uses a query parser in order to parse its content
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/query-dsl-query-string-query.html
 */
final class QueryString implements CompilableExpressionInterface
{
    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var bool
     */
    private $useLikeSyntax = false;


    /**
     * QueryString constructor.
     *
     * @param string $query
     */
    public function __construct(string $query)
    {
        $this->query($query);
    }

    /**
     * The actual query to be parsed. See Query string syntax.
     *
     * @param string $value
     *
     * @return $this
     */
    public function query(string $value): QueryString
    {
        $this->parameters['query'] = $value;

        return $this;
    }

    /**
     * The default field for query terms if no prefix field is specified.
     * Defaults to the index.query.default_field index settings, which in turn defaults to _all.
     *
     * @param string $value
     *
     * @return $this
     */
    public function defaultField(string $value): QueryString
    {
        $this->parameters['default_field'] = $value;

        return $this;
    }

    /**
     * The default operator used if no explicit operator is specified.
     * For example, with a default operator of OR, the query capital of Hungary is translated to capital
     * OR of OR Hungary, and with default operator of AND, the same query is translated to capital AND of AND Hungary.
     * The default value is OR.
     *
     * @param string $value
     *
     * @return $this
     */
    public function defaultOperator(string $value): QueryString
    {
        $this->parameters['default_operator'] = $value;

        return $this;
    }

    /**
     * Set the default operator as AND
     *
     * @return $this
     */
    public function and(): QueryString
    {
        return $this->defaultOperator('AND');
    }

    /**
     * Set the default operator as OR
     *
     * @return $this
     */
    public function or(): QueryString
    {
        return $this->defaultOperator('OR');
    }

    /**
     * The analyzer name used to analyze the query string.
     *
     * @param string $value
     *
     * @return $this
     */
    public function analyzer(string $value): QueryString
    {
        $this->parameters['analyzer'] = $value;

        return $this;
    }

    /**
     * When set, * or ? are allowed as the first character. Defaults to true.
     *
     * @param bool $value
     *
     * @return $this
     */
    public function allowLeadingWildcard(bool $value = true): QueryString
    {
        $this->parameters['allow_leading_wildcard'] = $value;

        return $this;
    }

    /**
     * Whether terms of wildcard, prefix, fuzzy, and range queries are to be automatically lower-cased
     * or not (since they are not analyzed).
     * Defaults to true.
     *
     * @param bool $value
     *
     * @return $this
     */
    public function lowercaseExpandedTerms(bool $value = true): QueryString
    {
        $this->parameters['lowercase_expanded_terms'] = $value;

        return $this;
    }

    /**
     * Set to true to enable position increments in result queries. Defaults to true.
     *
     * @param bool $value
     *
     * @return $this
     */
    public function enablePositionIncrements(bool $value = true): QueryString
    {
        $this->parameters['enable_position_increments'] = $value;

        return $this;
    }


    /**
     * Controls the number of terms fuzzy queries will expand to. Defaults to 50
     *
     * @param integer $value
     *
     * @return $this
     */
    public function fuzzyMaxExpansions(int $value): QueryString
    {
        $this->parameters['fuzzy_max_expansions'] = $value;

        return $this;
    }

    /**
     * Set the fuzziness for fuzzy queries. Defaults to AUTO. See Fuzziness edit for allowed settings.
     *
     * @param string $value
     *
     * @return $this
     */
    public function fuzziness(string $value): QueryString
    {
        $this->parameters['fuzziness'] = $value;

        return $this;
    }

    /**
     * Set the prefix length for fuzzy queries. Default is 0.
     *
     * @param integer $value
     *
     * @return $this
     */
    public function fuzzyPrefixLength(int $value): QueryString
    {
        $this->parameters['fuzzy_prefix_length'] = $value;

        return $this;
    }

    /**
     * Sets the default slop for phrases. If zero, then exact phrase matches are required. Default value is 0.
     *
     * @param integer $value
     *
     * @return $this
     */
    public function phraseSlop(int $value): QueryString
    {
        $this->parameters['phrase_slop'] = $value;

        return $this;
    }

    /**
     * Sets the boost value of the query. Defaults to 1.0.
     *
     * @param float $value
     *
     * @return $this
     */
    public function boost(float $value): QueryString
    {
        $this->parameters['boost'] = $value;

        return $this;
    }

    /**
     * By default, wildcards terms in a query string are not analyzed.
     * By setting this value to true, a best effort will be made to analyze those as well.
     *
     * @param bool $value
     *
     * @return $this
     */
    public function analyzeWildcard(bool $value = true): QueryString
    {
        $this->parameters['analyze_wildcard'] = $value;

        return $this;
    }

    /**
     * @param bool $useLikeSyntax
     *
     * @return $this
     */
    public function useLikeSyntax(bool $useLikeSyntax = true): QueryString
    {
        $this->useLikeSyntax = $useLikeSyntax;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(ElasticsearchGrammarInterface $grammar): array
    {
        $parameters = $this->parameters;

        if ($this->useLikeSyntax) {
            $parameters['query'] = $grammar->likeToWildcard($parameters['query']);
        }

        return ['query_string' => $parameters];
    }
}
