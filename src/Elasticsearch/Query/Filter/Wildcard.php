<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammarInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\CompilableExpressionInterface;

/**
 * Class Wildcard
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
     * @param bool $useLikeSyntax
     *
     * @return $this
     */
    public function useLikeSyntax($useLikeSyntax = true): Wildcard
    {
        $this->useLikeSyntax = (bool) $useLikeSyntax;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(ElasticsearchGrammarInterface $grammar)
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
