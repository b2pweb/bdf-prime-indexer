<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Filter;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammar;
use PHPUnit\Framework\TestCase;

/**
 * Class RangeTest
 */
class RangeTest extends TestCase
{
    /**
     *
     */
    public function test_lt()
    {
        $this->assertEquals(['range' => ['my_field' => ['lt' => 5]]], (new Range('my_field'))->lt(5)->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_lte()
    {
        $this->assertEquals(['range' => ['my_field' => ['lte' => 5]]], (new Range('my_field'))->lte(5)->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_gt()
    {
        $this->assertEquals(['range' => ['my_field' => ['gt' => 5]]], (new Range('my_field'))->gt(5)->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_gte()
    {
        $this->assertEquals(['range' => ['my_field' => ['gte' => 5]]], (new Range('my_field'))->gte(5)->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_boost()
    {
        $this->assertEquals(['range' => ['my_field' => ['boost' => 1.5, 'gt' => 3]]], (new Range('my_field'))->boost(1.5)->gt(3)->compile(new ElasticsearchGrammar()));
    }
}
