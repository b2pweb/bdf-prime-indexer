<?php

namespace Bdf\Prime\Indexer\Elasticsearch;

use Bdf\Collection\Util\Functor\Transformer\Getter;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\ClientInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapper;
use Bdf\Prime\Indexer\Elasticsearch\Query\ElasticsearchQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\MatchBoolean;
use Bdf\Prime\Indexer\Exception\InvalidQueryException;
use Bdf\Prime\Indexer\IndexTestCase;
use ElasticsearchTestFiles\City;
use ElasticsearchTestFiles\CityIndex;
use ElasticsearchTestFiles\ContainerEntity;
use ElasticsearchTestFiles\ContainerEntityIndex;
use ElasticsearchTestFiles\EmbeddedEntity;
use ElasticsearchTestFiles\UserIndex;
use ElasticsearchTestFiles\WithAnonAnalyzerIndex;

/**
 * Class ElasticsearchIndexTest
 */
class ElasticsearchIndexTest extends IndexTestCase
{
    /**
     * @var ElasticsearchIndex
     */
    private $index;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->index = $this->createIndex(new CityIndex());
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->index->drop();
    }

    /**
     *
     */
    public function test_add()
    {
        $this->index->add($city = new City([
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR',
            'zipCode' => '75000'
        ]));
        $this->assertNotEmpty($city->id());

        $this->index->refresh();

        $indexed = $this->index->query()->where(new MatchBoolean('name', 'Paris'))->stream()->first()->get();
        $this->assertEquals($city, $indexed);
    }

    /**
     *
     */
    public function test_add_with_already_set_id()
    {
        $this->index->add($city = new City([
            'id' => 1,
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR',
            'zipCode' => '75000'
        ]));
        $this->assertEquals(1, $city->id());

        $this->index->refresh();

        $indexed = $this->index->query()->where(new MatchBoolean('name', 'Paris'))->stream()->first()->get();
        $this->assertEquals($city, $indexed);
    }

    /**
     *
     */
    public function test_remove_without_id()
    {
        $this->expectException(InvalidQueryException::class);

        $this->index->remove(new City());
    }

    /**
     *
     */
    public function test_remove()
    {
        $this->addCities();

        $this->assertCount(4, $this->index->query()->stream());
        $city = $this->index->query()->where(new MatchBoolean('name', 'Cavaillon'))->stream()->first()->get();

        $this->index->remove($city);
        $this->index->refresh();

        $this->assertCount(3, $this->index->query()->stream());
        $this->assertCount(0, $this->index->query()->where(new MatchBoolean('name', 'Cavaillon'))->stream());
    }

    /**
     *
     */
    public function test_update()
    {
        $this->index->add($city = new City([
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR',
            'zipCode' => '75000'
        ]));

        $this->index->refresh();

        $city->setPopulation(2500000);
        $this->index->update($city);
        $this->index->refresh();

        $updated = $this->index->query()->stream()->first()->get();

        $this->assertEquals(2500000, $updated->population());
        $this->assertEquals($city, $updated);
    }

    /**
     *
     */
    public function test_update_with_attributes()
    {
        $this->index->add($city = new City([
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR',
            'zipCode' => '75000'
        ]));

        $this->index->refresh();

        $city->setPopulation(2500000);
        $city->setName('New name');
        $this->index->update($city, ['population']);
        $this->index->refresh();

        $updated = $this->index->query()->stream()->first()->get();

        $this->assertEquals(2500000, $updated->population());
        $this->assertEquals('Paris', $updated->name());
    }

    /**
     *
     */
    public function test_update_without_id()
    {
        $this->expectException(InvalidQueryException::class);

        $this->index->update(new City());
    }

    /**
     *
     */
    public function test_create_and_drop()
    {
        $this->addCities();

        $indexes = array_keys(self::$client->getAllAliases('test_cities'));
        $this->assertCount(1, $indexes);
        $this->assertStringStartsWith('test_cities_', $indexes[0]);

        $this->assertCount(4, $this->index->query()->stream());

        $this->index->drop();

        $this->assertFalse(self::$client->hasAlias('test_cities'));

        try {
            $this->index->query()->execute();
            $this->fail('Expects exception');
        } catch (\Exception $e) {
            $this->assertStringContainsString('index_not_found_exception', $e->getMessage());
        }
    }

    /**
     *
     */
    public function test_create_multiple_should_drop_previous_aliases()
    {
        $this->addCities();
        $this->addCities();

        $indexes = array_keys(self::$client->getAllAliases('test_cities'));
        $this->assertCount(1, $indexes);
        $this->assertStringStartsWith('test_cities_', $indexes[0]);

        $this->assertCount(4, $this->index->query()->stream());
    }

    /**
     *
     */
    public function test_create_multiple_disable_drop_previous_aliases()
    {
        $this->addCities(['dropPreviousIndexes' => false]);
        $this->addCities(['dropPreviousIndexes' => false]);

        $indexes = array_keys(self::$client->getAllAliases('test_cities'));
        $this->assertCount(2, $indexes);
        $this->assertStringStartsWith('test_cities_', $indexes[0]);
        $this->assertStringStartsWith('test_cities_', $indexes[1]);

        $this->assertCount(8, $this->index->query()->stream());
    }

    /**
     *
     */
    public function test_create_chunkSize()
    {
        $this->addCities(['chunkSize' => 3]);

        $this->assertCount(4, $this->index->query()->stream());
    }

    /**
     *
     */
    public function test_query()
    {
        $this->addCities();

        $this->assertInstanceOf(ElasticsearchQuery::class, $this->index->query());
        $this->assertEquals(
            ['Paris', 'Paris', 'Cavaillon', 'Parthenay'],
            $this->index->query()->stream()
                ->sort(function (City $a, City $b) { return $b->population() - $a->population(); })
                ->map(new Getter('name'))
                ->toArray(false)
        );
    }

    /**
     *
     */
    public function test_query_without_default_scope()
    {
        $this->addCities();

        $query = $this->index->query(false);

        $this->assertInstanceOf(ElasticsearchQuery::class, $query);
        $this->assertEquals(
            ['Paris', 'Paris', 'Cavaillon', 'Parthenay', 'Disabled'],
            $query->stream()
                ->sort(function (City $a, City $b) { return $b->population() - $a->population(); })
                ->map(new Getter('name'))
                ->toArray(false)
        );
    }

    /**
     *
     */
    public function test_scope()
    {
        $this->addCities();

        $this->assertEquals(
            ['Paris', 'Paris', 'Parthenay'],
            $this->index->matchName('par')->stream()->map(new Getter('name'))->toArray()
        );
    }

    /**
     *
     */
    public function test_scope_not_found()
    {
        $this->expectException(InvalidQueryException::class);
        $this->expectExceptionMessage('The scope "notFound" cannot be found for the entity "ElasticsearchTestFiles\City"');

        $this->index->notFound('par');
    }

    /**
     *
     */
    public function test_scope_using_filter()
    {
        $this->addCities();

        $this->assertEquals(
            ['Paris', 'Paris', 'Parthenay'],
            $this->index->query()->where('matchName', 'par')->stream()->map(new Getter('name'))->toArray()
        );
    }

    /**
     *
     */
    public function test_contains()
    {
        $this->index->add($city = new City([
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR',
            'zipCode' => '75000'
        ]));
        $this->index->refresh();

        $this->assertFalse($this->index->contains(new City()));
        $this->assertTrue($this->index->contains($city));

        $city->setId('not_found');
        $this->assertFalse($this->index->contains($city));
    }

    /**
     *
     */
    public function test_unit_create_schema()
    {
        $expected = [
            'settings' => [
                'analysis' => [
                    'analyzer' => [
                        'default' => [
                            'type'      => 'custom',
                            'tokenizer' => 'standard',
                            'filter'    => ['lowercase', 'asciifolding'],
                        ]
                    ],
                ],
            ],
            'mappings' => [
                'properties' => [
                    'name' => [
                        'type' => 'text'
                    ],
                    'population' => [
                        'type' => 'integer'
                    ],
                    'zipCode' => [
                        'type' => 'keyword'
                    ],
                    'country' => [
                        'index' => false,
                        'type' => 'keyword'
                    ],
                    'enabled' => [
                        'type' => 'boolean'
                    ],
                ],
            ],
        ];

        $client = $this->createMock(ClientInterface::class);
        $index = new ElasticsearchIndex($client, new ElasticsearchMapper(new CityIndex()));

        $client->expects($this->once())->method('createIndex')->with('test_cities', $expected);

        $index->create([], ['useAlias' => false]);
    }

    /**
     *
     */
    public function test_unit_create_schema_with_embedded()
    {
        $expected = [
            'settings' => [
                'analysis' => [
                    'analyzer' => [
                        'default' => [
                            'type' => 'standard',
                        ]
                    ],
                ],
            ],
            'mappings' => [
                'properties' => [
                    'name' => [
                        'type' => 'text'
                    ],
                    'foo' => [
                        'type' => 'object',
                        'properties' => [
                            'key' => ['type' => 'keyword'],
                            'value' => ['type' => 'integer'],
                        ],
                    ],
                    'bar' => [
                        'type' => 'object',
                        'properties' => [
                            'key' => ['type' => 'keyword'],
                            'value' => ['type' => 'integer'],
                        ],
                    ],
                ],
            ],
        ];

        $client = $this->createMock(ClientInterface::class);
        $index = new ElasticsearchIndex($client, new ElasticsearchMapper(new ContainerEntityIndex()));

        $client->expects($this->once())->method('createIndex')->with('containers', $expected);

        $index->create([], ['useAlias' => false]);
    }

    /**
     *
     */
    public function test_unit_create_schema_with_custom_analyzer()
    {
        $expected = [
            'settings' => [
                'analysis' => [
                    'analyzer' => [
                        'csv' => [
                            'type' => 'custom',
                            'tokenizer' => 'csv',
                        ],
                        'default' => [
                            'type' => 'standard',
                        ],
                    ],
                    'tokenizer' => [
                        'csv' => [
                            'type' => 'pattern',
                            'pattern' => ',',
                        ],
                    ],
                ],
            ],
            'mappings' => [
                'properties' => [
                    'name' => [
                        'type' => 'text',
                    ],
                    'email' => [
                        'type' => 'text',
                    ],
                    'login' => [
                        'type' => 'keyword',
                        'index' => false,
                    ],
                    'password' => [
                        'type' => 'keyword',
                        'index' => false,
                    ],
                    'roles' => [
                        'type' => 'text',
                        'analyzer' => 'csv',
                    ],
                ],
            ],
        ];

        $client = $this->createMock(ClientInterface::class);
        $index = new ElasticsearchIndex($client, new ElasticsearchMapper(new UserIndex()));

        $client->expects($this->once())->method('createIndex')->with('test_users', $expected);

        $index->create([], ['useAlias' => false]);
    }

    /**
     *
     */
    public function test_unit_create_schema_with_custom_anonymous_analyzer()
    {
        $expected = [
            'settings' => [
                'analysis' => [
                    'analyzer' => [
                        'values_anon_analyzer' => [
                            'type' => 'custom',
                            'tokenizer' => 'values_anon_analyzer',
                        ],
                        'default' => [
                            'type' => 'standard',
                        ],
                    ],
                    'tokenizer' => [
                        'values_anon_analyzer' => [
                            'type' => 'pattern',
                            'pattern' => ';',
                        ],
                    ],
                ],
            ],
            'mappings' => [
                'properties' => [
                    'name' => [
                        'type' => 'keyword',
                    ],
                    'values' => [
                        'type' => 'text',
                        'analyzer' => 'values_anon_analyzer',
                    ],
                ],
            ],
        ];

        $client = $this->createMock(ClientInterface::class);
        $index = new ElasticsearchIndex($client, new ElasticsearchMapper(new WithAnonAnalyzerIndex()));

        $client->expects($this->once())->method('createIndex')->with('test_anon_analyzers', $expected);

        $index->create([], ['useAlias' => false]);
    }

    /**
     *
     */
    public function test_refresh()
    {
        $this->addCities(['refresh' => false]);

        $this->assertEmpty($this->index->query()->all());
        $this->index->refresh();

        $this->assertCount(4, $this->index->query()->all());
    }

    /**
     *
     */
    private function addCities(array $options = [])
    {
        $this->index->create([
            new City([
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR',
                'zipCode' => '75000',
            ]),
            new City([
                'name' => 'Paris',
                'population' => 27022,
                'country' => 'US',
                'zipCode' => '75460',
            ]),
            new City([
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR',
                'zipCode' => '79200',
            ]),
            new City([
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR',
                'zipCode' => '84300',
            ]),
            new City([
                'name' => 'Disabled',
                'population' => 0,
                'country' => 'FR',
                'zipCode' => '000000',
                'enabled' => false,
            ]),
        ], $options + ['refresh' => true]);
    }

    public function test_embedded_functional()
    {
        $index = new ElasticsearchIndex(self::getClient(), new ElasticsearchMapper(new ContainerEntityIndex()));
        $index->create([
            $entity1 = (new ContainerEntity())
                ->setId('a')
                ->setName('Jean Machin')
                ->setFoo((new EmbeddedEntity())->setKey('abc')->setValue(123))
                ->setBar((new EmbeddedEntity())->setKey('xyz')->setValue(456)),
            $entity2 = (new ContainerEntity())
                ->setId('b')
                ->setName('François Bidule')
                ->setFoo((new EmbeddedEntity())->setKey('aqw')->setValue(741))
                ->setBar((new EmbeddedEntity())->setKey('zsx')->setValue(852)),
        ]);
        $index->refresh();

        $this->assertEqualsCanonicalizing([
            [
                'name' => 'Jean Machin',
                'foo' => [
                    'key' => 'abc',
                    'value' => 123,
                ],
                'bar' => [
                    'key' => 'xyz',
                    'value' => 456,
                ],
            ],
            [
                'name' => 'François Bidule',
                'foo' => [
                    'key' => 'aqw',
                    'value' => 741,
                ],
                'bar' => [
                    'key' => 'zsx',
                    'value' => 852,
                ],
            ],
        ], array_map(fn ($a) => $a['_source'], $index->query()->execute()->hits()));
        $this->assertEqualsCanonicalizing([$entity1, $entity2], $index->query()->all());
    }
}
