<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammar;
use PHPUnit\Framework\TestCase;

/**
 * Class MissingTest
 */
class MissingTest extends TestCase
{
    /**
     *
     */
    public function test_compile()
    {
        $this->assertEquals(['missing' => ['field' => 'my_field']], (new Missing('my_field'))->compile(new ElasticsearchGrammar()));
    }
}
