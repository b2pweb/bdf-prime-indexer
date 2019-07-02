<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammar;
use PHPUnit\Framework\TestCase;

/**
 * Class WildcardTest
 */
class WildcardTest extends TestCase
{
    /**
     *
     */
    public function test_simple()
    {
        $this->assertEquals(['wildcard' => ['my_field' => 'my*search?']], (new Wildcard('my_field', 'my*search?'))->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_likeSyntax()
    {
        $this->assertEquals(['wildcard' => ['my_field' => 'my*search\?']], (new Wildcard('my_field', 'my%search?'))->useLikeSyntax()->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_prefix()
    {
        $this->assertEquals(['prefix' => ['my_field' => 'my_search']], (new Wildcard('my_field', 'my_search*'))->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_not_prefix()
    {
        $this->assertEquals(['wildcard' => ['my_field' => 'my?search*']], (new Wildcard('my_field', 'my?search*'))->compile(new ElasticsearchGrammar()));
        $this->assertEquals(['wildcard' => ['my_field' => 'my*search*']], (new Wildcard('my_field', 'my*search*'))->compile(new ElasticsearchGrammar()));
        $this->assertEquals(['wildcard' => ['my_field' => 'my_search\*']], (new Wildcard('my_field', 'my_search\*'))->compile(new ElasticsearchGrammar()));
    }
}
