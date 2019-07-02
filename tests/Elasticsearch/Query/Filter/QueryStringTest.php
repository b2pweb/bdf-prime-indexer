<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammar;
use PHPUnit\Framework\TestCase;

/**
 * Class QueryStringTest
 */
class QueryStringTest extends TestCase
{
    /**
     *
     */
    public function test_basic()
    {
        $this->assertEquals(['query_string' => ['query' => 'my_terms']], (new QueryString('my_terms'))->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_defaultField()
    {
        $this->assertEquals(['query_string' => ['query' => 'my_terms', 'default_field' => 'my_field']], (new QueryString('my_terms'))->defaultField('my_field')->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_or()
    {
        $this->assertEquals(['query_string' => ['query' => 'my_terms', 'default_operator' => 'OR']], (new QueryString('my_terms'))->or()->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_and()
    {
        $this->assertEquals(['query_string' => ['query' => 'my_terms', 'default_operator' => 'AND']], (new QueryString('my_terms'))->and()->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_analyzer()
    {
        $this->assertEquals(['query_string' => ['query' => 'my_terms', 'analyzer' => 'my_analyzer']], (new QueryString('my_terms'))->analyzer('my_analyzer')->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_allowLeadingWildcard()
    {
        $this->assertEquals(['query_string' => ['query' => 'my_terms', 'allow_leading_wildcard' => true]], (new QueryString('my_terms'))->allowLeadingWildcard()->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_lowercaseExpandedTerms()
    {
        $this->assertEquals(['query_string' => ['query' => 'my_terms', 'lowercase_expanded_terms' => true]], (new QueryString('my_terms'))->lowercaseExpandedTerms()->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_enablePositionIncrements()
    {
        $this->assertEquals(['query_string' => ['query' => 'my_terms', 'enable_position_increments' => true]], (new QueryString('my_terms'))->enablePositionIncrements()->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_fuzzyMaxExpansions()
    {
        $this->assertEquals(['query_string' => ['query' => 'my_terms', 'fuzzy_max_expansions' => 25]], (new QueryString('my_terms'))->fuzzyMaxExpansions(25)->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_fuzziness()
    {
        $this->assertEquals(['query_string' => ['query' => 'my_terms', 'fuzziness' => 'test']], (new QueryString('my_terms'))->fuzziness('test')->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_fuzzyPrefixLength()
    {
        $this->assertEquals(['query_string' => ['query' => 'my_terms', 'fuzzy_prefix_length' => 12]], (new QueryString('my_terms'))->fuzzyPrefixLength(12)->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_phraseSlop()
    {
        $this->assertEquals(['query_string' => ['query' => 'my_terms', 'phrase_slop' => 12]], (new QueryString('my_terms'))->phraseSlop(12)->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_boost()
    {
        $this->assertEquals(['query_string' => ['query' => 'my_terms', 'boost' => 1.2]], (new QueryString('my_terms'))->boost(1.2)->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_analyzeWildcard()
    {
        $this->assertEquals(['query_string' => ['query' => 'my_terms', 'analyze_wildcard' => true]], (new QueryString('my_terms'))->analyzeWildcard()->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_useLikeSyntax()
    {
        $this->assertEquals(['query_string' => ['query' => 'my*like?query']], (new QueryString('my%like_query'))->useLikeSyntax()->compile(new ElasticsearchGrammar()));
    }
}
