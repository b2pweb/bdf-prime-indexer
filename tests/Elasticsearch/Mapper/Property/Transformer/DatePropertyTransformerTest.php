<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Transformer;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\StandardAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\SimplePropertyAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Property;
use PHPUnit\Framework\TestCase;

class DatePropertyTransformerTest extends TestCase
{
    public function test_with_explicit_php_format()
    {
        $transformer = new DatePropertyTransformer('Y-m-d');
        $property = new Property('foo', [], new StandardAnalyzer(), 'date', new SimplePropertyAccessor('foo'));

        $this->assertSame(null, $transformer->toIndex($property, null));
        $this->assertSame('2022-10-12', $transformer->toIndex($property, '2022-10-12'));
        $this->assertSame('2022-10-12', $transformer->toIndex($property, new \DateTime('2022-10-12')));

        $this->assertSame(null, $transformer->fromIndex($property, null));
        $this->assertEquals(new \DateTime('2022-10-12'), $transformer->fromIndex($property, '2022-10-12'));
    }

    public function test_with_explicit_php_format_timestamp()
    {
        $transformer = new DatePropertyTransformer('U');
        $property = new Property('foo', [], new StandardAnalyzer(), 'date', new SimplePropertyAccessor('foo'));

        $this->assertSame(null, $transformer->toIndex($property, null));
        $this->assertSame(78541257, $transformer->toIndex($property, 78541257));
        $this->assertEquals(1665525600, $transformer->toIndex($property, new \DateTime('2022-10-12')));

        $this->assertSame(null, $transformer->fromIndex($property, null));
        $this->assertEquals(new \DateTime('2022-10-12'), $transformer->fromIndex($property, 1665525600));
    }

    public function test_without_format()
    {
        $transformer = new DatePropertyTransformer();
        $property = new Property('foo', [], new StandardAnalyzer(), 'date', new SimplePropertyAccessor('foo'));

        $this->assertSame(null, $transformer->toIndex($property, null));
        $this->assertSame('2022-10-12', $transformer->toIndex($property, '2022-10-12'));
        $this->assertSame('2022-10-12T00:00:00+02:00', $transformer->toIndex($property, new \DateTime('2022-10-12')));

        $this->assertSame(null, $transformer->fromIndex($property, null));
        $this->assertEquals(new \DateTime('2022-10-12'), $transformer->fromIndex($property, '2022-10-12'));
        $this->assertEquals(new \DateTime('2022-10-12T15:35:00+00:00'), $transformer->fromIndex($property, '2022-10-12T15:35:00+00:00'));
    }

    public function test_with_property_declaration_format()
    {
        $transformer = new DatePropertyTransformer();
        $property = new Property('foo', ['format' => 'yyyy-MM-dd'], new StandardAnalyzer(), 'date', new SimplePropertyAccessor('foo'));

        $this->assertSame(null, $transformer->toIndex($property, null));
        $this->assertSame('2022-10-12', $transformer->toIndex($property, '2022-10-12'));
        $this->assertSame('2022-10-12', $transformer->toIndex($property, new \DateTime('2022-10-12')));

        $this->assertSame(null, $transformer->fromIndex($property, null));
        $this->assertEquals(new \DateTime('2022-10-12'), $transformer->fromIndex($property, '2022-10-12'));
    }
}
