<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammar;
use PHPUnit\Framework\TestCase;

/**
 * Class MatchTest
 */
class MatchTest extends TestCase
{
    /**
     *
     */
    public function test_compile()
    {
        $this->assertEquals(['match' => ['my_field' => 'my_value']], (new MatchBoolean('my_field', 'my_value'))->compile(new ElasticsearchGrammar()));
    }
}
