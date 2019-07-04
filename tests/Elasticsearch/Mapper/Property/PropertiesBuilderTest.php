<?php

namespace Elasticsearch\Mapper\Property;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\CsvAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\StandardAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapper;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\SimplePropertyAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertiesBuilder;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Property;
use PHPUnit\Framework\TestCase;

/**
 * Class PropertiesBuilder
 */
class PropertiesBuilderTest extends TestCase
{
    /**
     * @var PropertiesBuilder
     */
    private $builder;

    protected function setUp()
    {
        $this->builder = new PropertiesBuilder(new ElasticsearchMapper(new \UserIndex()));
    }

    /**
     * @dataProvider provideTypeMethods
     */
    public function test_build($type, $expected)
    {
        $this->builder->$type('my_field');

        $this->assertEquals(['my_field' => $expected], $this->builder->build());
    }

    /**
     *
     */
    public function test_analyzer_not_found()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Analyzer not_found is not declared');

        $this->builder->string('my_field')->analyzer('not_found');
    }

    /**
     *
     */
    public function test_analyzer()
    {
        $this->builder->string('my_field')->analyzer('csv');
        $this->assertEquals(['my_field' => new Property('my_field', ['analyzer' => 'csv'], new CsvAnalyzer(), 'string', new SimplePropertyAccessor('my_field'))], $this->builder->build());
    }

    /**
     *
     */
    public function test_notAnalyzed()
    {
        $this->builder->string('my_field')->notAnalyzed();
        $this->assertEquals(['my_field' => new Property('my_field', ['index' => 'not_analyzed'], new StandardAnalyzer(), 'string', new SimplePropertyAccessor('my_field'))], $this->builder->build());
    }

    /**
     *
     */
    public function test_accessor_string()
    {
        $this->builder->string('my_field')->accessor('other_field');
        $this->assertEquals(['my_field' => new Property('my_field', [], new StandardAnalyzer(), 'string', new SimplePropertyAccessor('other_field'))], $this->builder->build());
    }

    /**
     *
     */
    public function test_accessor_instance()
    {
        $accessor = $this->createMock(PropertyAccessorInterface::class);

        $this->builder->string('my_field')->accessor($accessor);
        $this->assertEquals(['my_field' => new Property('my_field', [], new StandardAnalyzer(), 'string', $accessor)], $this->builder->build());
    }

    /**
     *
     */
    public function test_accessor_invalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid accessor given');

        $this->builder->string('my_field')->accessor(null);
    }

    /**
     * @return array
     */
    public function provideTypeMethods()
    {
        return [
            ['string', new Property('my_field', [], new StandardAnalyzer(), 'string', new SimplePropertyAccessor('my_field'))],
            ['long', new Property('my_field', [], new StandardAnalyzer(), 'long', new SimplePropertyAccessor('my_field'))],
            ['integer', new Property('my_field', [], new StandardAnalyzer(), 'integer', new SimplePropertyAccessor('my_field'))],
            ['short', new Property('my_field', [], new StandardAnalyzer(), 'short', new SimplePropertyAccessor('my_field'))],
            ['byte', new Property('my_field', [], new StandardAnalyzer(), 'byte', new SimplePropertyAccessor('my_field'))],
            ['double', new Property('my_field', [], new StandardAnalyzer(), 'double', new SimplePropertyAccessor('my_field'))],
            ['float', new Property('my_field', [], new StandardAnalyzer(), 'float', new SimplePropertyAccessor('my_field'))],
            ['date', new Property('my_field', [], new StandardAnalyzer(), 'date', new SimplePropertyAccessor('my_field'))],
            ['boolean', new Property('my_field', [], new StandardAnalyzer(), 'boolean', new SimplePropertyAccessor('my_field'))],
            ['binary', new Property('my_field', [], new StandardAnalyzer(), 'binary', new SimplePropertyAccessor('my_field'))],
        ];
    }
}
