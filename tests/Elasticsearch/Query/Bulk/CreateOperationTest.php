<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Bulk;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapper;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapperInterface;
use ElasticsearchTestFiles\City;
use ElasticsearchTestFiles\CityIndex;
use PHPUnit\Framework\TestCase;

class CreateOperationTest extends TestCase
{
    public function test_default()
    {
        $op = new CreateOperation(['foo' => 'bar']);

        $this->assertSame('create', $op->name());
        $this->assertSame([], $op->metadata($this->createMock(ElasticsearchMapperInterface::class)));
        $this->assertSame(['foo' => 'bar'], $op->value($this->createMock(ElasticsearchMapperInterface::class)));
    }

    public function test_with_id_on_document()
    {
        $op = new CreateOperation(['foo' => 'bar', '_id' => '45']);

        $this->assertSame('create', $op->name());
        $this->assertSame(['_id' => '45'], $op->metadata($this->createMock(ElasticsearchMapperInterface::class)));
        $this->assertSame(['foo' => 'bar'], $op->value($this->createMock(ElasticsearchMapperInterface::class)));
    }

    public function test_with_id()
    {
        $op = new CreateOperation(['foo' => 'bar']);
        $op->id('45');

        $this->assertSame('create', $op->name());
        $this->assertSame(['_id' => '45'], $op->metadata($this->createMock(ElasticsearchMapperInterface::class)));
        $this->assertSame(['foo' => 'bar'], $op->value($this->createMock(ElasticsearchMapperInterface::class)));
    }

    public function test_with_entity()
    {
        $op = new CreateOperation(new City([
            'id' => 3,
            'name' => 'Parthenay',
            'population' => 11599,
            'country' => 'FR',
        ]));

        $mapper = new ElasticsearchMapper(new CityIndex());

        $this->assertSame('create', $op->name());
        $this->assertSame(['_id' => 3], $op->metadata($mapper));
        $this->assertEquals([
            'name' => 'Parthenay',
            'population' => 11599,
            'country' => 'FR',
            'enabled' => true,
            'zipCode' => null,
        ], $op->value($mapper));
    }

    public function test_with_options()
    {
        $op = new CreateOperation(['foo' => 'bar']);
        $op
            ->option('require_alias', true)
            ->option('dynamic_templates', ['aaa' => 'bbb'])
        ;

        $this->assertSame('create', $op->name());
        $this->assertSame(['require_alias' => true, 'dynamic_templates' => ['aaa' => 'bbb']], $op->metadata($this->createMock(ElasticsearchMapperInterface::class)));
        $this->assertSame(['foo' => 'bar'], $op->value($this->createMock(ElasticsearchMapperInterface::class)));
    }
}
