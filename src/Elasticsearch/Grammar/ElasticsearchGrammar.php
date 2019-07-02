<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Grammar;

use Bdf\Prime\Indexer\Elasticsearch\Query\CompilableExpressionInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\Wildcard;
use UnexpectedValueException;

/**
 * Handle the grammar for elasticsearch query operators
 */
class ElasticsearchGrammar implements ElasticsearchGrammarInterface
{
    /**
     * {@inheritdoc}
     */
    public function escape($value)
    {
        $value = str_replace('\\', '\\\\', $value);

        return str_replace(
            ['+', '-', '=', '&&', '||', '>', '<', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '/'],
            ['\+', '\-', '\=', '\&\&', '\|\|', '\>', '\<', '\!', '\(', '\)', '\{', '\}', '\[', '\]', '\^', '\"', '\~', '\*', '\?', '\:', '\/'],
            $value
        );
    }

    /**
     * {@inheritdoc}
     */
    public function operator($field, $operator, $value)
    {
        switch ($operator) {
            case '<':
            case ':lt':
                return ['range' => [$field => ['lt' => $this->escape($value)]]];

            case '<=':
            case ':lte':
                return ['range' => [$field => ['lte' => $this->escape($value)]]];

            case '>':
            case ':gt':
                return ['range' => [$field => ['gt' => $this->escape($value)]]];

            case '>=':
            case ':gte':
                return ['range' => [$field => ['gte' => $this->escape($value)]]];

            // REGEX matching
            case '~=':
            case '=~':
            case ':regex':
                if (is_array($value)) {
                    return $this->or(array_map(function ($value) use($field) { return $this->operator($field, ':regex', $value); }, $value));
                }

                return ['regexp' => [$field => ['value' => $value]]];

            // LIKE
            case ':like':
                if (is_array($value)) {
                    return $this->or(array_map(function ($value) use($field) { return $this->operator($field, ':like', $value); }, $value));
                }

                return (new Wildcard($field, $value))->useLikeSyntax()->compile($this);

            // In
            case 'in':
            case ':in':
                if (empty($value)) {
                    return ['missing' => ['field' => $field]];
                }
                return ['terms' => [$field => array_map([$this, 'escape'], $value)]];

            // Not in
            case 'notin':
            case '!in':
            case ':notin':
                if (empty($value)) {
                    return ['exists' => ['field' => $field]];
                }
                return $this->not($this->operator($field, 'in', $value));

            // Between
            case 'between':
            case ':between':
                return ['range' => [$field => ['gte' => $this->escape($value[0]), 'lte' => $this->escape($value[1])]]];

            // Not between
            case '!between':
            case ':notbetween':
                return $this->not($this->operator($field, 'between', $value));

            // Not equal
            case '<>':
            case '!=':
            case ':ne':
            case ':not':
                if (is_null($value)) {
                    return ['exists' => ['field' => $field]];
                }
                if (is_array($value)) {
                    return $this->operator($field, ':notin', $value);
                }
                return $this->not(['term' => [$field => $this->escape($value)]]);

            // Equals
            case '=':
            case ':eq':
                if (is_null($value)) {
                    return ['missing' => ['field' => $field]];
                }
                if (is_array($value)) {
                    return $this->operator($field, ':in', $value);
                }
                return ['term' => [$field => $this->escape($value)]];

            // Unsupported operator
            default:
                throw new UnexpectedValueException("Unsupported operator '".$operator."' in WHERE clause");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function not($expression)
    {
        if ($expression instanceof CompilableExpressionInterface) {
            $expression = $expression->compile($this);
        }

        return ['bool' => ['must_not' => [$expression]]];
    }

    /**
     * {@inheritdoc}
     */
    public function or(array $expressions)
    {
        $should = [];

        foreach ($expressions as $expression) {
            if ($expression instanceof CompilableExpressionInterface) {
                $expression = $expression->compile($this);
            }

            $should[] = $expression;
        }

        return ['bool' => ['minimum_should_match' => 1,  'should' => $should]];
    }

    /**
     * {@inheritdoc}
     */
    public function likeToWildcard($query, bool $escape = true)
    {
        if ($escape) {
            $query = $this->escape($query);
        }

        return strtr($query, '%_', '*?');
    }
}
