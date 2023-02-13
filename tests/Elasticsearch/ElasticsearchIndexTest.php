<?php

namespace Bdf\Prime\Indexer\Elasticsearch;

use Bdf\Collection\Util\Functor\Transformer\Getter;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\ClientInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapper;
use Bdf\Prime\Indexer\Elasticsearch\Query\Bulk\ElasticsearchBulkQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\Bulk\UpdateOperation;
use Bdf\Prime\Indexer\Elasticsearch\Query\ElasticsearchCreateQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\ElasticsearchQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\Expression\Script;
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

        $index->create([], function (ElasticsearchCreateIndexOptions $options) { $options->useAlias = false; });
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

    public function test_create_using_bulk_query()
    {
        $this->addCities(function (ElasticsearchCreateIndexOptions $options) {
            $options->useBulkWriteQuery = true;
            $options->refresh = true;
        });

        $this->assertCount(4, $this->index->query()->all());
    }

    public function test_create_using_query_configurator()
    {
        $this->addCities(function (ElasticsearchCreateIndexOptions $options) {
            $options->queryConfigurator = function (ElasticsearchCreateQuery $query, City $entity) {
                $query->values([
                    '_id' => md5($entity->country() . ' ' . $entity->name()),
                    'name' => $entity->name(),
                    'population' => $entity->population() * 2,
                    'country' => $entity->country(),
                    'zipCode' => $entity->zipCode(),
                    'enabled' => $entity->enabled(),
                ]);
            };
            $options->refresh = true;
        });

        $this->assertEqualsCanonicalizing([
            new City([
                'id' => '863420f5439b8a8d3f7bed9b65df4912',
                'name' => 'Paris',
                'population' => 4403156,
                'country' => 'FR',
                'zipCode' => '75000',
            ]),
            new City([
                'id' => 'b4440d111360422d46ac098b87ceea1d',
                'name' => 'Paris',
                'population' => 54044,
                'country' => 'US',
                'zipCode' => '75460',
            ]),
            new City([
                'id' => 'f34ca5d6719c86c6972286290b6fa507',
                'name' => 'Parthenay',
                'population' => 23198,
                'country' => 'FR',
                'zipCode' => '79200',
            ]),
            new City([
                'id' => '72c62d08d8d4425130e80553ac31fc20',
                'name' => 'Cavaillon',
                'population' => 53378,
                'country' => 'FR',
                'zipCode' => '84300',
            ]),
        ], $this->index->query()->all());
    }

    public function test_create_with_upsert()
    {
        $this->index->create(
            [
                new City([
                    'name' => 'Paris',
                    'population' => 2201578,
                    'country' => 'FR',
                    'zipCode' => '75000',
                ]),
                new City([
                    'name' => 'Paris',
                    'population' => 150000,
                    'country' => 'FR',
                    'zipCode' => '75000',
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
            ],
            function (ElasticsearchCreateIndexOptions $options) {
                $options->useBulkWriteQuery = true;
                $options->queryConfigurator = function (ElasticsearchBulkQuery $query, City $entity) {
                    $query->update(fn (UpdateOperation $op) => $op
                        ->id(strtolower($entity->name()))
                        ->script(new Script('ctx._source.population += params.population', Script::LANG_PAINLESS, [
                            'population' => $entity->population(),
                        ]))
                        ->upsert($entity)
                    );
                };
                $options->refresh = true;
            }
        );

        $this->assertEqualsCanonicalizing([
            new City([
                'id' => 'paris',
                'name' => 'Paris',
                'population' => 2351578,
                'country' => 'FR',
                'zipCode' => '75000',
            ]),
            new City([
                'id' => 'parthenay',
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR',
                'zipCode' => '79200',
            ]),
            new City([
                'id' => 'cavaillon',
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR',
                'zipCode' => '84300',
            ]),
        ], $this->index->query()->all());
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
    public function test_refresh_using_closure_configurator()
    {
        $this->addCities(function (ElasticsearchCreateIndexOptions $opt) { $opt->refresh = false; });

        $this->assertEmpty($this->index->query()->all());
        $this->index->refresh();

        $this->assertCount(4, $this->index->query()->all());
    }

    public function test_updateQuery()
    {
        $this->addCities();

        $this->index->updateQuery()
            ->script('ctx._source.population += 10000')
            ->id(
                $this->index->query()->where('name', 'cavaillon')->first()->id()->get()
            )
            ->execute()
        ;

        $this->index->refresh();
        $this->assertEquals(36689, $this->index->query()->where('name', 'cavaillon')->first()->population()->get());
    }

    public function test_bulk()
    {
        $this->addCities();

        $this->index->bulk()
            ->update(fn (UpdateOperation $op) => $op
                ->id($this->index->query()->where('name', 'cavaillon')->first()->id()->get())
                ->script('ctx._source.population += 10000')
            )
            ->update(fn (UpdateOperation $op) => $op
                ->id($this->index->query()->where('name', 'parthenay')->first()->id()->get())
                ->script('ctx._source.population += 10000')
            )
            ->refresh()
            ->execute()
        ;

        $this->assertEquals(36689, $this->index->query()->where('name', 'cavaillon')->first()->population()->get());
        $this->assertEquals(21599, $this->index->query()->where('name', 'parthenay')->first()->population()->get());
    }

    public function test_create_error()
    {
        $this->expectExceptionMessage('failed to parse field [population] of type [integer] in document');

        $this->index->create(
            [
                new City([
                    'population' => 'invalid',
                    'enabled' => 'invalid'
                ]),
            ],
            ['refresh' => true]
        );
    }

    /**
     * @param array|callable $options
     */
    private function addCities($options = [])
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
        ], is_array($options) ? $options + ['refresh' => true] : $options);
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
