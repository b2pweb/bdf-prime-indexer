<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammarInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\CompilableExpressionInterface;

/**
 * Class QueryString
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
    public function __construct($query)
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
    public function query($value)
    {
        $this->parameters['query'] = $value;

        return $this;
    }

    /**
     * The default field for query terms if no prefix field is specified. Defaults to the index.query.default_field index settings, which in turn defaults to _all.
     *
     * @param string $value
     *
     * @return $this
     */
    public function defaultField($value)
    {
        $this->parameters['default_field'] = $value;

        return $this;
    }

    /**
     * The default operator used if no explicit operator is specified. For example, with a default operator of OR, the query capital of Hungary is translated to capital OR of OR Hungary, and with default operator of AND, the same query is translated to capital AND of AND Hungary. The default value is OR.
     *
     * @param string $value
     *
     * @return $this
     */
    public function defaultOperator($value)
    {
        $this->parameters['default_operator'] = $value;

        return $this;
    }

    /**
     * Set the default operator as AND
     *
     * @return $this
     */
    public function and()
    {
        return $this->defaultOperator('AND');
    }

    /**
     * Set the default operator as OR
     *
     * @return $this
     */
    public function or()
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
    public function analyzer($value)
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
    public function allowLeadingWildcard(bool $value = true)
    {
        $this->parameters['allow_leading_wildcard'] = $value;

        return $this;
    }

    /**
     * Whether terms of wildcard, prefix, fuzzy, and range queries are to be automatically lower-cased or not (since they are not analyzed). Defaults to true.
     *
     * @param bool $value
     *
     * @return $this
     */
    public function lowercaseExpandedTerms(bool $value = true)
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
    public function enablePositionIncrements(bool $value = true)
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
    public function fuzzyMaxExpansions($value)
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
    public function fuzziness($value)
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
    public function fuzzyPrefixLength($value)
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
    public function phraseSlop($value)
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
    public function boost($value)
    {
        $this->parameters['boost'] = $value;

        return $this;
    }

    /**
     * By default, wildcards terms in a query string are not analyzed. By setting this value to true, a best effort will be made to analyze those as well.
     *
     * @param bool $value
     *
     * @return $this
     */
    public function analyzeWildcard(bool $value = true)
    {
        $this->parameters['analyze_wildcard'] = $value;

        return $this;
    }

    /**
     * @param bool $useLikeSyntax
     *
     * @return $this
     */
    public function useLikeSyntax(bool $useLikeSyntax = true)
    {
        $this->useLikeSyntax = $useLikeSyntax;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(ElasticsearchGrammarInterface $grammar)
    {
        $parameters = $this->parameters;

        if ($this->useLikeSyntax) {
            $parameters['query'] = $grammar->likeToWildcard($parameters['query']);
        }

        return ['query_string' => $parameters];
    }
}
