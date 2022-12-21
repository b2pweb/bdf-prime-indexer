<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Bulk;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapper;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapperInterface;
use Bdf\Prime\Indexer\Exception\InvalidQueryException;
use ElasticsearchTestFiles\City;
use ElasticsearchTestFiles\CityIndex;
use PHPUnit\Framework\TestCase;

class UpdateOperationTest extends TestCase
{
    public function test_missing_id()
    {
        $this->expectException(InvalidQueryException::class);
        $op = new UpdateOperation();

        $op->metadata($this->createMock(ElasticsearchMapperInterface::class));
    }

    public function test_default()
    {
        $op = new UpdateOperation(['foo' => 'bar']);
        $op->id('45');

        $this->assertSame('update', $op->name());
        $this->assertSame(['_id' => '45'], $op->metadata($this->createMock(ElasticsearchMapperInterface::class)));
        $this->assertSame(['doc' => ['foo' => 'bar']], $op->value($this->createMock(ElasticsearchMapperInterface::class)));
    }

    public function test_with_id_on_document()
    {
        $op = new UpdateOperation(['foo' => 'bar', '_id' => '45']);

        $this->assertSame('update', $op->name());
        $this->assertSame(['_id' => '45'], $op->metadata($this->createMock(ElasticsearchMapperInterface::class)));
        $this->assertSame(['doc' => ['foo' => 'bar']], $op->value($this->createMock(ElasticsearchMapperInterface::class)));
    }

    public function test_with_entity()
    {
        $op = new UpdateOperation(new City([
            'id' => 3,
            'name' => 'Parthenay',
            'population' => 11599,
            'country' => 'FR',
        ]));

        $mapper = new ElasticsearchMapper(new CityIndex());

        $this->assertSame('update', $op->name());
        $this->assertSame(['_id' => '3'], $op->metadata($mapper));
        $this->assertEquals(['doc' => [
            'name' => 'Parthenay',
            'population' => 11599,
            'country' => 'FR',
            'enabled' => true,
            'zipCode' => null,
        ]], $op->value($mapper));
    }

    public function test_with_options()
    {
        $op = new UpdateOperation(['foo' => 'bar']);
        $op
            ->id('foo')
            ->option('require_alias', true)
            ->option('dynamic_templates', ['aaa' => 'bbb'])
        ;

        $this->assertSame('update', $op->name());
        $this->assertSame(['require_alias' => true, 'dynamic_templates' => ['aaa' => 'bbb'], '_id' => 'foo'], $op->metadata($this->createMock(ElasticsearchMapperInterface::class)));
        $this->assertSame(['doc' => ['foo' => 'bar']], $op->value($this->createMock(ElasticsearchMapperInterface::class)));
    }

    public function test_with_upsert_as_doc()
    {
        $op = new UpdateOperation(new City([
            'id' => 3,
            'name' => 'Parthenay',
            'population' => 11599,
            'country' => 'FR',
        ]));

        $op->upsert();

        $mapper = new ElasticsearchMapper(new CityIndex());

        $this->assertSame('update', $op->name());
        $this->assertSame(['_id' => '3'], $op->metadata($mapper));
        $this->assertEquals([
            'doc' => [
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR',
                'enabled' => true,
                'zipCode' => null,
            ],
            'doc_as_upsert' => true,
        ], $op->value($mapper));
    }

    public function test_with_upsert_doc()
    {
        $op = new UpdateOperation(['population' => 11599]);

        $op->id(3);
        $op->upsert(new City([
            'name' => 'Parthenay',
            'population' => 11599,
            'country' => 'FR',
        ]));

        $mapper = new ElasticsearchMapper(new CityIndex());

        $this->assertSame('update', $op->name());
        $this->assertSame(['_id' => '3'], $op->metadata($mapper));
        $this->assertEquals([
            'doc' => [
                'population' => 11599,
            ],
            'upsert' => [
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR',
                'enabled' => true,
                'zipCode' => null,
            ],
        ], $op->value($mapper));
    }

    public function test_with_script_upsert_doc()
    {
        $op = new UpdateOperation();

        $op->id(3);
        $op->script('ctx._source.population += 1000');
        $op->upsert(new City([
            'name' => 'Parthenay',
            'population' => 11599,
            'country' => 'FR',
        ]));

        $mapper = new ElasticsearchMapper(new CityIndex());

        $this->assertSame('update', $op->name());
        $this->assertSame(['_id' => '3'], $op->metadata($mapper));
        $this->assertEquals([
            'script' => 'ctx._source.population += 1000',
            'upsert' => [
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR',
                'enabled' => true,
                'zipCode' => null,
            ],
        ], $op->value($mapper));
    }
}
