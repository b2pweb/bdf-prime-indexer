<?php

namespace Bdf\Prime\Indexer\Elasticsearch;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ElasticsearchCreateIndexOptionsTest extends TestCase
{
    public function test_defaults()
    {
        $opts = new ElasticsearchCreateIndexOptions();

        $this->assertTrue($opts->useAlias);
        $this->assertTrue($opts->dropPreviousIndexes);
        $this->assertFalse($opts->refresh);
        $this->assertFalse($opts->useBulkWriteQuery);
        $this->assertSame(5000, $opts->chunkSize);
        $this->assertNull($opts->queryConfigurator);
        $this->assertNull($opts->logger);
    }

    public function test_fromArray()
    {
        $this->assertFalse(ElasticsearchCreateIndexOptions::fromArray(['useAlias' => false])->useAlias);
        $this->assertFalse(ElasticsearchCreateIndexOptions::fromArray(['dropPreviousIndexes' => false])->dropPreviousIndexes);
        $this->assertTrue(ElasticsearchCreateIndexOptions::fromArray(['useBulkWriteQuery' => true])->useBulkWriteQuery);
        $this->assertTrue(ElasticsearchCreateIndexOptions::fromArray(['refresh' => true])->refresh);
        $this->assertSame(123, ElasticsearchCreateIndexOptions::fromArray(['chunkSize' => 123])->chunkSize);
        $this->assertSame($c = function () {}, ElasticsearchCreateIndexOptions::fromArray(['queryConfigurator' => $c])->queryConfigurator);
        $this->assertSame($l = new NullLogger(), ElasticsearchCreateIndexOptions::fromArray(['logger' => $l])->logger);

        $opts = ElasticsearchCreateIndexOptions::fromArray([
            'dropPreviousIndexes' => false,
            'chunkSize' => 1000,
            'refresh' => true,
        ]);

        $this->assertTrue($opts->useAlias);
        $this->assertFalse($opts->dropPreviousIndexes);
        $this->assertSame(1000, $opts->chunkSize);
        $this->assertNull($opts->queryConfigurator);
        $this->assertNull($opts->logger);
        $this->assertTrue($opts->refresh);
        $this->assertFalse($opts->useBulkWriteQuery);
    }

    public function test_fromOptions_with_closure()
    {
        $opts = ElasticsearchCreateIndexOptions::fromOptions(function (ElasticsearchCreateIndexOptions $opts) {
            $opts->dropPreviousIndexes = false;
            $opts->chunkSize = 1000;
            $opts->refresh = true;
        });

        $this->assertTrue($opts->useAlias);
        $this->assertFalse($opts->dropPreviousIndexes);
        $this->assertSame(1000, $opts->chunkSize);
        $this->assertNull($opts->queryConfigurator);
        $this->assertNull($opts->logger);
        $this->assertTrue($opts->refresh);
        $this->assertFalse($opts->useBulkWriteQuery);
    }

    public function test_fromOptions_with_array()
    {
        $opts = ElasticsearchCreateIndexOptions::fromOptions([
            'dropPreviousIndexes' => false,
            'chunkSize' => 1000,
        ]);

        $this->assertTrue($opts->useAlias);
        $this->assertFalse($opts->dropPreviousIndexes);
        $this->assertSame(1000, $opts->chunkSize);
        $this->assertNull($opts->queryConfigurator);
        $this->assertNull($opts->logger);
        $this->assertFalse($opts->refresh);
        $this->assertFalse($opts->useBulkWriteQuery);
    }
}
