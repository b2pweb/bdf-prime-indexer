<?php

namespace Elasticsearch\Mapper\Property;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\Types;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\CsvAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\StandardAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\SimplePropertyAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\ObjectProperty;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Property;
use ElasticsearchTestFiles\ContainerEntity;
use ElasticsearchTestFiles\EmbeddedEntity;
use PHPUnit\Framework\TestCase;

class ObjectPropertyTest extends TestCase
{
    /**
     *
     */
    public function test_getters()
    {
        $property = new ObjectProperty('city', \City::class, [
            'name' => new Property('name', [], new StandardAnalyzer(), 'text', new SimplePropertyAccessor('name')),
            'zipCode' => new Property('zipCode', [], new StandardAnalyzer(), 'keyword', new SimplePropertyAccessor('zipCode')),
            'country' => new Property('country', [], new StandardAnalyzer(), 'keyword', new SimplePropertyAccessor('country')),
        ], new SimplePropertyAccessor('city'));

        $this->assertEquals('city', $property->name());
        $this->assertEquals([
            'properties' => [
                'name' => ['type' => 'text'],
                'zipCode' => ['type' => 'keyword'],
                'country' => ['type' => 'keyword'],
            ],
        ], $property->declaration());
        $this->assertEquals('object', $property->type());
        $this->assertEquals(new SimplePropertyAccessor('city'), $property->accessor());

        $property = new ObjectProperty('city', \City::class, [
            'name' => new Property('name', [], new StandardAnalyzer(), 'text', new SimplePropertyAccessor('name')),
            'zipCode' => new Property('zipCode', [], new StandardAnalyzer(), 'keyword', new SimplePropertyAccessor('zipCode')),
            'country' => new Property('country', [], new StandardAnalyzer(), 'keyword', new SimplePropertyAccessor('country')),
        ], new SimplePropertyAccessor('city'), Types::NESTED);
        $this->assertEquals('nested', $property->type());
    }

    /**
     *
     */
    public function test_readFromModel()
    {
        $object = new ContainerEntity();

        $property = new ObjectProperty('foo', EmbeddedEntity::class, [
            'key' => new Property('key', [], new StandardAnalyzer(), 'keyword', new SimplePropertyAccessor('key')),
            'value' => new Property('value', [], new StandardAnalyzer(), 'integer', new SimplePropertyAccessor('value')),
        ], new SimplePropertyAccessor('foo'));

        $this->assertNull($property->readFromModel($object));
        $this->assertSame(['key' => 'a', 'value' => 4], $property->readFromModel($object->setFoo((new EmbeddedEntity())->setKey('a')->setValue(4))));
    }

    /**
     *
     */
    public function test_readFromModel_array()
    {
        $object = new ContainerEntity();

        $property = new ObjectProperty('baz', EmbeddedEntity::class, [
            'key' => new Property('key', [], new StandardAnalyzer(), 'keyword', new SimplePropertyAccessor('key')),
            'value' => new Property('value', [], new StandardAnalyzer(), 'integer', new SimplePropertyAccessor('value')),
        ], new SimplePropertyAccessor('baz'));

        $this->assertSame([], $property->readFromModel($object));

        $object->setBaz([
            (new EmbeddedEntity())->setKey('a')->setValue(4),
            (new EmbeddedEntity())->setKey('b')->setValue(5),
        ]);

        $this->assertSame(
            [
                ['key' => 'a', 'value' => 4],
                ['key' => 'b', 'value' => 5],
            ],
            $property->readFromModel($object->setFoo((new EmbeddedEntity())->setKey('a')->setValue(4)))
        );
    }

    /**
     *
     */
    public function test_writeToModel()
    {
        $object = new ContainerEntity();

        $property = new ObjectProperty('foo', EmbeddedEntity::class, [
            'key' => new Property('key', [], new StandardAnalyzer(), 'keyword', new SimplePropertyAccessor('key')),
            'value' => new Property('value', [], new StandardAnalyzer(), 'integer', new SimplePropertyAccessor('value')),
        ], new SimplePropertyAccessor('foo'));

        $property->writeToModel($object, []);

        $this->assertEquals(new EmbeddedEntity(), $object->foo());

        $foo = $object->foo();
        $property->writeToModel($object, ['key' => 'aqw']);

        $this->assertSame($foo, $object->foo());
        $this->assertEquals((new EmbeddedEntity())->setKey('aqw'), $object->foo());

        $property->writeToModel($object, ['key' => 'aqw', 'value' => 412, 'ignored' => 'eeeee']);
        $this->assertEquals((new EmbeddedEntity())->setKey('aqw')->setValue(412), $object->foo());
    }

    /**
     *
     */
    public function test_writeToModel_array()
    {
        $object = new ContainerEntity();

        $property = new ObjectProperty('baz', EmbeddedEntity::class, [
            'key' => new Property('key', [], new StandardAnalyzer(), 'keyword', new SimplePropertyAccessor('key')),
            'value' => new Property('value', [], new StandardAnalyzer(), 'integer', new SimplePropertyAccessor('value')),
        ], new SimplePropertyAccessor('baz'));

        $property->writeToModel($object, []);
        $this->assertSame([], $object->baz());

        $property->writeToModel($object, ['key' => 'aqw']);

        $this->assertEquals([(new EmbeddedEntity())->setKey('aqw')], $object->baz());

        $property->writeToModel($object, ['key' => 'aqw', 'value' => 412, 'ignored' => 'eeeee']);
        $this->assertEquals([(new EmbeddedEntity())->setKey('aqw')->setValue(412)], $object->baz());

        $property->writeToModel($object, [
            ['key' => 'aqw', 'value' => 412],
            ['key' => 'aqw2', 'value' => 4122],
        ]);
        $this->assertEquals([
            (new EmbeddedEntity())->setKey('aqw')->setValue(412),
            (new EmbeddedEntity())->setKey('aqw2')->setValue(4122),
        ], $object->baz());
    }
}
