<?php

namespace Elasticsearch\Mapper\Analyzer;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\CsvAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * Class CsvAnalyzerTest
 */
class CsvAnalyzerTest extends TestCase
{
    /**
     *
     */
    public function test_getters()
    {
        $analyzer = new CsvAnalyzer(';', ['lowercase']);

        $this->assertEquals(['type' => 'custom'], $analyzer->declaration());
        $this->assertEquals([
            'type' => 'pattern',
            'pattern' => ';'
        ], $analyzer->tokenizer());
        $this->assertEquals(['lowercase'], $analyzer->filters());
    }

    /**
     *
     */
    public function test_fromIndex()
    {
        $analyzer = new CsvAnalyzer();

        $this->assertEquals(['3', '7'], $analyzer->fromIndex('3,7'));
    }

    /**
     *
     */
    public function test_toIndex()
    {
        $analyzer = new CsvAnalyzer();

        $this->assertEquals('3,7', $analyzer->toIndex(['3', '7']));
    }
}
