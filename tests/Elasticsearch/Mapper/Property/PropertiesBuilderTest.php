<?php

namespace Elasticsearch\Mapper\Property;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\ArrayAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\CsvAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\StandardAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapper;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\CustomAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\EmbeddedAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\ReadOnlyAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\SimplePropertyAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertiesBuilder;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Property;
use ElasticsearchTestFiles\UserIndex;
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

    protected function setUp(): void
    {
        $this->builder = new PropertiesBuilder(new ElasticsearchMapper(new UserIndex()));
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

        $this->builder->text('my_field')->analyzer('not_found');
    }

    /**
     *
     */
    public function test_analyzer()
    {
        $this->builder->text('my_field')->analyzer('csv');
        $this->assertEquals(['my_field' => new Property('my_field', ['analyzer' => 'csv'], new CsvAnalyzer(), 'text', new SimplePropertyAccessor('my_field'))], $this->builder->build());
    }

    /**
     *
     */
    public function test_disableIndexing()
    {
        $this->builder->text('my_field')->disableIndexing();
        $this->assertEquals(['my_field' => new Property('my_field', ['index' => false], new StandardAnalyzer(), 'text', new SimplePropertyAccessor('my_field'))], $this->builder->build());
    }

    /**
     *
     */
    public function test_accessor_string()
    {
        $this->builder->text('my_field')->accessor('other_field');
        $this->assertEquals(['my_field' => new Property('my_field', [], new StandardAnalyzer(), 'text', new SimplePropertyAccessor('other_field'))], $this->builder->build());
    }

    /**
     *
     */
    public function test_accessor_instance()
    {
        $accessor = $this->createMock(PropertyAccessorInterface::class);

        $this->builder->keyword('my_field')->accessor($accessor);
        $this->assertEquals(['my_field' => new Property('my_field', [], new StandardAnalyzer(), 'keyword', $accessor)], $this->builder->build());
    }

    /**
     *
     */
    public function test_accessor_closure()
    {
        $this->builder->text('my_field')->accessor(function () {});
        $this->assertEquals(['my_field' => new Property('my_field', [], new StandardAnalyzer(), 'text', new CustomAccessor(function () {}))], $this->builder->build());
    }

    /**
     *
     */
    public function test_accessor_embedded()
    {
        $this->builder->keyword('my_field')->accessor(['embedded' => \User::class, 'name']);
        $this->assertEquals(['my_field' => new Property('my_field', [], new StandardAnalyzer(), 'keyword', new EmbeddedAccessor(['embedded' => \User::class, 'name']))], $this->builder->build());
    }

    /**
     *
     */
    public function test_accessor_invalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid accessor given');

        $this->builder->keyword('my_field')->accessor(null);
    }

    /**
     *
     */
    public function test_readOnly()
    {
        $this->builder->keyword('my_field')->readOnly();
        $this->assertEquals(['my_field' => new Property('my_field', [], new StandardAnalyzer(), 'keyword', new ReadOnlyAccessor('my_field'))], $this->builder->build());
    }

    /**
     *
     */
    public function test_readOnly_with_custom_accessor()
    {
        $this->builder->keyword('my_field')->accessor('field')->readOnly();
        $this->assertEquals(['my_field' => new Property('my_field', [], new StandardAnalyzer(), 'keyword', new ReadOnlyAccessor(new SimplePropertyAccessor('field')))], $this->builder->build());
    }

    /**
     *
     */
    public function test_anonymous_analyzer()
    {
        $this->builder->text('my_field')->analyzer($analyzer = new CsvAnalyzer());
        $this->assertEquals(['my_field' => new Property('my_field', ['analyzer' => 'my_field_anon_analyzer'], $analyzer, 'text', new SimplePropertyAccessor('my_field'))], $this->builder->build());
        $this->assertSame(['my_field_anon_analyzer' => $analyzer], $this->builder->analyzers());
    }

    /**
     *
     */
    public function test_anonymous_analyzer_array()
    {
        $this->builder->text('my_field')->analyzer(['foo' => 'bar']);
        $this->assertEquals(['my_field' => new Property('my_field', ['analyzer' => 'my_field_anon_analyzer'], new ArrayAnalyzer(['foo' => 'bar']), 'text', new SimplePropertyAccessor('my_field'))], $this->builder->build());
        $this->assertEquals(['my_field_anon_analyzer' => new ArrayAnalyzer(['foo' => 'bar'])], $this->builder->analyzers());
    }

    /**
     *
     */
    public function test_anonymous_analyzer_invalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder->text('my_field')->analyzer(new \stdClass());
    }

    /**
     *
     */
    public function test_csv()
    {
        $this->builder->csv('values');
        $this->assertEquals(['values' => new Property('values', ['analyzer' => 'csv_44'], new CsvAnalyzer(), 'text', new SimplePropertyAccessor('values'))], $this->builder->build());
        $this->assertEquals(['csv_44' => new CsvAnalyzer()], $this->builder->analyzers());

        $this->builder->csv('other');

        $this->assertEquals([
            'values' => new Property('values', ['analyzer' => 'csv_44'], new CsvAnalyzer(), 'text', new SimplePropertyAccessor('values')),
            'other' => new Property('other', ['analyzer' => 'csv_44'], new CsvAnalyzer(), 'text', new SimplePropertyAccessor('other')),
        ], $this->builder->build());
        $this->assertEquals(['csv_44' => new CsvAnalyzer()], $this->builder->analyzers());

        $this->builder->csv('new_separator', ';');

        $this->assertEquals([
            'values' => new Property('values', ['analyzer' => 'csv_44'], new CsvAnalyzer(), 'text', new SimplePropertyAccessor('values')),
            'other' => new Property('other', ['analyzer' => 'csv_44'], new CsvAnalyzer(), 'text', new SimplePropertyAccessor('other')),
            'new_separator' => new Property('new_separator', ['analyzer' => 'csv_59'], new CsvAnalyzer(';'), 'text', new SimplePropertyAccessor('new_separator')),
        ], $this->builder->build());
        $this->assertEquals([
            'csv_44' => new CsvAnalyzer(),
            'csv_59' => new CsvAnalyzer(';'),
        ], $this->builder->analyzers());
    }

    /**
     *
     */
    public function test_csv_with_custom_type()
    {
        $this->builder->csv('values', ',', 'string');
        $this->assertEquals(['values' => new Property('values', ['analyzer' => 'csv_44'], new CsvAnalyzer(), 'string', new SimplePropertyAccessor('values'))], $this->builder->build());
    }

    /**
     *
     */
    public function test_fields()
    {
        $this->builder->keyword('name')->field('raw', ['type' => 'keyword', 'index' => 'not_analyzed']);
        $this->assertEquals([
            'fields' => [
                'raw' => [
                    'type' => 'keyword',
                    'index' => 'not_analyzed',
                ]
            ]
        ], $this->builder->build()['name']->declaration());

        $this->builder->field('other', ['type' => 'number']);
        $this->assertEquals([
            'fields' => [
                'raw' => [
                    'type' => 'keyword',
                    'index' => 'not_analyzed',
                ],
                'other' => [
                    'type' => 'number',
                ]
            ]
        ], $this->builder->build()['name']->declaration());
    }

    /**
     * @return array
     */
    public function provideTypeMethods()
    {
        return [
            ['text', new Property('my_field', [], new StandardAnalyzer(), 'text', new SimplePropertyAccessor('my_field'))],
            ['keyword', new Property('my_field', [], new StandardAnalyzer(), 'keyword', new SimplePropertyAccessor('my_field'))],
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
