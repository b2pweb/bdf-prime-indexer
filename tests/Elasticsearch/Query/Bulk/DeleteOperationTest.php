<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Bulk;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapperInterface;
use PHPUnit\Framework\TestCase;

class DeleteOperationTest extends TestCase
{
    public function test_default()
    {
        $op = new DeleteOperation('foo');

        $this->assertSame('delete', $op->name());
        $this->assertSame(['_id' => 'foo'], $op->metadata($this->createMock(ElasticsearchMapperInterface::class)));
        $this->assertNull($op->value($this->createMock(ElasticsearchMapperInterface::class)));
    }

    public function test_with_options()
    {
        $op = new DeleteOperation('foo');
        $op
            ->option('require_alias', true)
        ;

        $this->assertSame('delete', $op->name());
        $this->assertSame(['_id' => 'foo', 'require_alias' => true], $op->metadata($this->createMock(ElasticsearchMapperInterface::class)));
        $this->assertNull($op->value($this->createMock(ElasticsearchMapperInterface::class)));
    }
}
