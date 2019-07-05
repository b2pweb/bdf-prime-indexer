<?php

namespace Bdf\Prime\Indexer\Elasticsearch;

use Bdf\Collection\Util\Functor\Transformer\Getter;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapper;
use Bdf\Prime\Indexer\Elasticsearch\Query\ElasticsearchQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\Match;
use City;
use CityIndex;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Namespaces\IndicesNamespace;
use PHPUnit\Framework\TestCase;

/**
 * Class ElasticsearchIndexTest
 */
class ElasticsearchIndexTest extends TestCase
{
    /**
     * @var ElasticsearchIndex
     */
    private $index;

    /**
     * @var Client
     */
    private $client;

    /**
     *
     */
    protected function setUp()
    {
        $this->index = new ElasticsearchIndex(
            $this->client = ClientBuilder::fromConfig([
                'hosts' => ['127.0.0.1:9222']
            ]),
            new ElasticsearchMapper(new CityIndex())
        );
    }

    /**
     *
     */
    protected function tearDown()
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

        sleep(1);

        $indexed = $this->index->query()->where(new Match('name', 'Paris'))->stream()->first()->get();
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

        sleep(1);

        $indexed = $this->index->query()->where(new Match('name', 'Paris'))->stream()->first()->get();
        $this->assertEquals($city, $indexed);
    }

    /**
     *
     */
    public function test_remove_without_id()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->index->remove(new City());
    }

    /**
     *
     */
    public function test_remove()
    {
        $this->addCities();

        $this->assertCount(4, $this->index->query()->stream());
        $city = $this->index->query()->where(new Match('name', 'Cavaillon'))->stream()->first()->get();

        $this->index->remove($city);
        sleep(1);

        $this->assertCount(3, $this->index->query()->stream());
        $this->assertCount(0, $this->index->query()->where(new Match('name', 'Cavaillon'))->stream());
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

        sleep(1);

        $city->setPopulation(2500000);
        $this->index->update($city);
        sleep(1);

        $updated = $this->index->query()->stream()->first()->get();

        $this->assertEquals(2500000, $updated->population());
        $this->assertEquals($city, $updated);
    }

    /**
     *
     */
    public function test_update_without_id()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->index->update(new City());
    }

    /**
     *
     */
    public function test_create_and_drop()
    {
        $this->addCities();

        $indexes = array_keys($this->client->indices()->getAlias(['name' => 'test_cities']));
        $this->assertCount(1, $indexes);
        $this->assertStringStartsWith('test_cities_', $indexes[0]);

        $this->assertCount(4, $this->index->query()->stream());

        $this->index->drop();

        $this->assertFalse($this->client->indices()->existsAlias(['name' => 'test_cities']));

        try {
            $this->index->query()->execute();
            $this->fail('Expects exception');
        } catch (Missing404Exception $e) {
            $this->assertContains('index_not_found_exception', $e->getMessage());
        }
    }

    /**
     *
     */
    public function test_create_multiple_should_drop_previous_aliases()
    {
        $this->addCities();
        $this->addCities();

        $indexes = array_keys($this->client->indices()->getAlias(['name' => 'test_cities']));
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

        $indexes = array_keys($this->client->indices()->getAlias(['name' => 'test_cities']));
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
                ->toArray()
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
                ->toArray()
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
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('The scope notFound cannot be found');

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
        sleep(1);

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
            'index' => 'test_cities',
            'body' => [
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
                    'city' => [
                        'properties' => [
                            'name' => [
                                'type' => 'string'
                            ],
                            'population' => [
                                'type' => 'integer'
                            ],
                            'zipCode' => [
                                'type' => 'string'
                            ],
                            'country' => [
                                'index' => 'not_analyzed',
                                'type' => 'string'
                            ],
                            'enabled' => [
                                'type' => 'boolean'
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $client = $this->createMock(Client::class);
        $indices = $this->createMock(IndicesNamespace::class);
        $index = new ElasticsearchIndex($client, new ElasticsearchMapper(new CityIndex()));

        $client->expects($this->any())->method('indices')->willReturn($indices);
        $indices->expects($this->once())->method('create')->with($expected);

        $index->create([], ['useAlias' => false]);
    }

    /**
     *
     */
    public function test_unit_create_schema_with_custom_analyzer()
    {
        $expected = [
            'index' => 'test_users',
            'body' => [
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
                    'user' => [
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                            ],
                            'email' => [
                                'type' => 'string',
                            ],
                            'login' => [
                                'type' => 'string',
                                'index' => 'not_analyzed',
                            ],
                            'password' => [
                                'type' => 'string',
                                'index' => 'not_analyzed',
                            ],
                            'roles' => [
                                'type' => 'string',
                                'analyzer' => 'csv',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $client = $this->createMock(Client::class);
        $indices = $this->createMock(IndicesNamespace::class);
        $index = new ElasticsearchIndex($client, new ElasticsearchMapper(new \UserIndex()));

        $client->expects($this->any())->method('indices')->willReturn($indices);
        $indices->expects($this->once())->method('create')->with($expected);

        $index->create([], ['useAlias' => false]);
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
        ], $options);

        sleep(2);
    }
}
