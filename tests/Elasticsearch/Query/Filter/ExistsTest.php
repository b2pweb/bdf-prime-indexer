<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammar;
use PHPUnit\Framework\TestCase;

/**
 * Class ExistsTest
 */
class ExistsTest extends TestCase
{
    /**
     *
     */
    public function test_compile()
    {
        $this->assertEquals(['exists' => ['field' => 'my_field']], (new Exists('my_field'))->compile(new ElasticsearchGrammar()));
    }
}
