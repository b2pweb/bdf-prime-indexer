<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammarInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\CompilableExpressionInterface;

/**
 * Find documents where the field specified contains terms which match the pattern specified,
 * where the pattern supports single character wildcards (?) and multi-character wildcards (*)
 *
 * The SQL LIKE syntax is also supported, if enabled using `useLikeSyntax(true)`
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/query-dsl-wildcard-query.html
 */
final class Wildcard implements CompilableExpressionInterface
{
    /**
     * @var bool
     */
    private $useLikeSyntax = false;

    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $value;


    /**
     * Wildcard constructor.
     *
     * @param string $field
     * @param string $value
     */
    public function __construct(string $field, string $value)
    {
        $this->field = $field;
        $this->value = $value;
    }

    /**
     * Enable (or disable) the SQL LIKE syntax (_ for any chars, % for any strings)
     * If enabled, the search term is automatically escaped
     *
     * @param bool $useLikeSyntax
     *
     * @return $this
     */
    public function useLikeSyntax(bool $useLikeSyntax = true): Wildcard
    {
        $this->useLikeSyntax = (bool) $useLikeSyntax;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(ElasticsearchGrammarInterface $grammar): array
    {
        $value = $this->value;

        if ($this->useLikeSyntax) {
            $value = $grammar->likeToWildcard($value);
        }

        if ($this->isPrefixSearch($value)) {
            return ['prefix' => [$this->field => substr($value, 0, -1)]];
        }

        return ['wildcard' => [$this->field => $value]];
    }

    private function isPrefixSearch($value)
    {
        // Do not ends with wildcard, or wildcard is escaped
        if ($value{-1} !== '*' || $value{-2} === '\\') {
            return false;
        }

        // Filter has "?" metacharacter
        if (substr_count($value, '?') > substr_count($value, '\?')) {
            return false;
        }

        // Filter has "*" metacharacter other than last one
        if (substr_count($value, '*', 0, -1) > substr_count($value, '\*', 0, -1)) {
            return false;
        }

        return true;
    }
}
