<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammar;
use PHPUnit\Framework\TestCase;

/**
 * Class WhereFilterTest
 */
class WhereFilterTest extends TestCase
{
    /**
     *
     */
    public function test_with_operator()
    {
        $this->assertEquals(['wildcard' => ['my_field' => 'my*value']], (new WhereFilter('my_field', ':like', 'my%value'))->compile(new ElasticsearchGrammar()));
    }
}
