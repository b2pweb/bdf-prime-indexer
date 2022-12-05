<?php

namespace Bdf\Prime\Indexer;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class CreateIndexOptionsTest extends TestCase
{
    public function test_defaults()
    {
        $opts = new CreateIndexOptions();

        $this->assertTrue($opts->useAlias);
        $this->assertTrue($opts->dropPreviousIndexes);
        $this->assertSame(5000, $opts->chunkSize);
        $this->assertNull($opts->queryConfigurator);
        $this->assertNull($opts->logger);
    }

    public function test_fromArray()
    {
        $this->assertFalse(CreateIndexOptions::fromArray(['useAlias' => false])->useAlias);
        $this->assertFalse(CreateIndexOptions::fromArray(['dropPreviousIndexes' => false])->dropPreviousIndexes);
        $this->assertSame(123, CreateIndexOptions::fromArray(['chunkSize' => 123])->chunkSize);
        $this->assertSame($c = function () {}, CreateIndexOptions::fromArray(['queryConfigurator' => $c])->queryConfigurator);
        $this->assertSame($l = new NullLogger(), CreateIndexOptions::fromArray(['logger' => $l])->logger);

        $opts = CreateIndexOptions::fromArray([
            'dropPreviousIndexes' => false,
            'chunkSize' => 1000,
        ]);

        $this->assertTrue($opts->useAlias);
        $this->assertFalse($opts->dropPreviousIndexes);
        $this->assertSame(1000, $opts->chunkSize);
        $this->assertNull($opts->queryConfigurator);
        $this->assertNull($opts->logger);
    }

    public function test_fromOptions_with_closure()
    {
        $opts = CreateIndexOptions::fromOptions(function (CreateIndexOptions $opts) {
            $opts->dropPreviousIndexes = false;
            $opts->chunkSize = 1000;
        });

        $this->assertTrue($opts->useAlias);
        $this->assertFalse($opts->dropPreviousIndexes);
        $this->assertSame(1000, $opts->chunkSize);
        $this->assertNull($opts->queryConfigurator);
        $this->assertNull($opts->logger);
    }

    public function test_fromOptions_with_array()
    {
        $opts = CreateIndexOptions::fromOptions([
            'dropPreviousIndexes' => false,
            'chunkSize' => 1000,
        ]);

        $this->assertTrue($opts->useAlias);
        $this->assertFalse($opts->dropPreviousIndexes);
        $this->assertSame(1000, $opts->chunkSize);
        $this->assertNull($opts->queryConfigurator);
        $this->assertNull($opts->logger);
    }
}
