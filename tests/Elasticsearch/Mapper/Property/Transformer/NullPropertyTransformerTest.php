<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Transformer;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\StandardAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\SimplePropertyAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Property;
use PHPUnit\Framework\TestCase;

class NullPropertyTransformerTest extends TestCase
{
    public function test()
    {
        $this->assertSame(NullPropertyTransformer::instance(), NullPropertyTransformer::instance());
        $property = new Property('country', ['index' => 'not_analyzed'], new StandardAnalyzer(), 'string', new SimplePropertyAccessor('country'));

        $this->assertSame('foo', NullPropertyTransformer::instance()->fromIndex($property, 'foo'));
        $this->assertSame('foo', NullPropertyTransformer::instance()->toIndex($property, 'foo'));
    }
}
