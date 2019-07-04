<?php

namespace Elasticsearch\Mapper\Analyzer;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\ArrayAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * Class ArrayAnalyzerTest
 */
class ArrayAnalyzerTest extends TestCase
{
    /**
     *
     */
    public function test_simple_declaration()
    {
        $analyzer = new ArrayAnalyzer(['type' => 'standard']);

        $this->assertEquals(['type' => 'standard'], $analyzer->declaration());
        $this->assertNull($analyzer->tokenizer());
        $this->assertEquals([], $analyzer->filters());
    }

    /**
     *
     */
    public function test_with_filters()
    {
        $analyzer = new ArrayAnalyzer(['type' => 'standard', 'filter' => ['my_filter']]);

        $this->assertEquals(['type' => 'standard'], $analyzer->declaration());
        $this->assertNull($analyzer->tokenizer());
        $this->assertEquals(['my_filter'], $analyzer->filters());
    }

    /**
     *
     */
    public function test_with_tokenizer()
    {
        $analyzer = new ArrayAnalyzer(['type' => 'standard', 'tokenizer' => 'my_tokenizer']);

        $this->assertEquals(['type' => 'standard'], $analyzer->declaration());
        $this->assertEquals('my_tokenizer', $analyzer->tokenizer());
        $this->assertEquals([], $analyzer->filters());
    }

    /**
     *
     */
    public function test_from_to_index()
    {
        $analyzer = new ArrayAnalyzer(['type' => 'standard']);

        $this->assertEquals(42, $analyzer->toIndex(42));
        $this->assertEquals(42, $analyzer->fromIndex(42));
    }
}
