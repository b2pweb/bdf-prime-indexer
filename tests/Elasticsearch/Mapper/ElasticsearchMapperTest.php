<?php

namespace Elasticsearch\Mapper;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\ArrayAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\CsvAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\StandardAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapper;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\SimplePropertyAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertiesBuilder;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Property;
use Bdf\Prime\Indexer\IndexTestCase;
use City;
use PHPUnit\Framework\TestCase;
use WithAnonAnalyzerIndex;

/**
 * Class ElasticsearchMapperTest
 */
class ElasticsearchMapperTest extends IndexTestCase
{
    /**
     * @var ElasticsearchMapper
     */
    private $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ElasticsearchMapper(new \CityIndex());
    }

    /**
     *
     */
    public function test_configuration()
    {
        $this->assertInstanceOf(\CityIndex::class, $this->mapper->configuration());
    }

    /**
     *
     */
    public function test_analyzers()
    {
        $analyzers = $this->mapper->analyzers();

        $this->assertEquals([
            'default' => new ArrayAnalyzer([
                'type'      => 'custom',
                'tokenizer' => 'standard',
                'filter'    => ['lowercase', 'asciifolding'],
            ])
        ], $analyzers);

        $this->assertSame($analyzers, $this->mapper->analyzers());
    }

    /**
     *
     */
    public function test_properties()
    {
        $properties = $this->mapper->properties();

        if (self::minimalElasticsearchVersion('5.0')) {
            $expected = [
                'name' => new Property('name', [], $this->mapper->analyzers()['default'], 'text', new SimplePropertyAccessor('name')),
                'population' => new Property('population', [], $this->mapper->analyzers()['default'], 'integer', new SimplePropertyAccessor('population')),
                'zipCode' => new Property('zipCode', [], $this->mapper->analyzers()['default'], 'keyword', new SimplePropertyAccessor('zipCode')),
                'country' => new Property('country', ['index' => false], $this->mapper->analyzers()['default'], 'keyword', new SimplePropertyAccessor('country')),
                'enabled' => new Property('enabled', [], $this->mapper->analyzers()['default'], 'boolean', new SimplePropertyAccessor('enabled')),
            ];
        } else {
            $expected = [
                'name' => new Property('name', [], $this->mapper->analyzers()['default'], 'string', new SimplePropertyAccessor('name')),
                'population' => new Property('population', [], $this->mapper->analyzers()['default'], 'integer', new SimplePropertyAccessor('population')),
                'zipCode' => new Property('zipCode', [], $this->mapper->analyzers()['default'], 'string', new SimplePropertyAccessor('zipCode')),
                'country' => new Property('country', ['index' => 'not_analyzed'], $this->mapper->analyzers()['default'], 'string', new SimplePropertyAccessor('country')),
                'enabled' => new Property('enabled', [], $this->mapper->analyzers()['default'], 'boolean', new SimplePropertyAccessor('enabled')),
            ];
        }

        $this->assertEquals($expected, $properties);

        $this->assertSame($properties, $this->mapper->properties());
    }

    /**
     *
     */
    public function test_scopes()
    {
        $this->assertEquals((new \CityIndex())->scopes(), $this->mapper->scopes());
        $this->assertSame($this->mapper->scopes(), $this->mapper->scopes());
    }

    /**
     *
     */
    public function test_toIndex_bad_entity()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Entity must be an instance of City');

        $this->mapper->toIndex(new \stdClass());
    }

    /**
     *
     */
    public function test_toIndex()
    {
        $this->assertEquals(
            [
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR',
                'zipCode' => '75000',
                '_id' => 5,
                'enabled' => true,
            ],
            $this->mapper->toIndex(new City([
                'id'  => 5,
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR',
                'zipCode' => '75000',
            ]))
        );

        $this->assertEquals(
            [
                'name' => 'Paris',
                'country' => 'FR',
                '_id' => 5,
            ],
            $this->mapper->toIndex(new City([
                'id'  => 5,
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR',
                'zipCode' => '75000',
            ]), ['name', 'country'])
        );
    }

    /**
     *
     */
    public function test_fromIndex()
    {
        $this->assertEquals(
            new City([
                'id'  => 5,
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR',
                'zipCode' => '75000',
            ]),
            $this->mapper->fromIndex([
                '_id' => 5,
                '_source' => [
                    'name' => 'Paris',
                    'population' => 2201578,
                    'country' => 'FR',
                    'zipCode' => '75000',
                    'enabled' => true,
                ]
            ])
        );
    }

    /**
     *
     */
    public function test_id()
    {
        $entity = new City(['id' => 5]);

        $this->assertEquals(5, $this->mapper->id($entity));
    }

    /**
     *
     */
    public function test_id_without_accessor()
    {
        $config = $this->createMock(ElasticsearchIndexConfigurationInterface::class);
        $mapper = new ElasticsearchMapper($config);
        $entity = new City(['id' => 5]);

        $this->assertNull($mapper->id($entity));
    }

    /**
     *
     */
    public function test_setId()
    {
        $entity = new City();
        $this->mapper->setId($entity, 5);

        $this->assertEquals(5, $entity->id());
    }

    /**
     *
     */
    public function test_setId_without_accessor()
    {
        $config = $this->createMock(ElasticsearchIndexConfigurationInterface::class);
        $mapper = new ElasticsearchMapper($config);
        $entity = new City();

        $mapper->setId($entity, 5);

        $this->assertNull($entity->id());
    }

    /**
     *
     */
    public function test_with_anonymous_analyzers()
    {
        $mapper = new ElasticsearchMapper(new WithAnonAnalyzerIndex());

        $this->assertEquals([
            'values_anon_analyzer' => new CsvAnalyzer(';'),
            'default' => new StandardAnalyzer(),
        ], $mapper->analyzers());

        $this->assertEquals([
            'name' => new Property('name', [], $mapper->analyzers()['default'], 'string', new SimplePropertyAccessor('name')),
            'values' => new Property('values', ['analyzer' => 'values_anon_analyzer'], $mapper->analyzers()['values_anon_analyzer'], 'string', new SimplePropertyAccessor('values')),
        ], $mapper->properties());
    }
}
