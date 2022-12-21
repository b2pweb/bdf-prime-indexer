<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Bulk;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapper;
use Bdf\Prime\Indexer\Elasticsearch\Query\ElasticsearchQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\MatchBoolean;
use Bdf\Prime\Indexer\IndexTestCase;
use ElasticsearchTestFiles\City;
use ElasticsearchTestFiles\CityIndex;

class ElasticsearchBulkQueryTest extends IndexTestCase
{
    private ElasticsearchBulkQuery $query;
    private ElasticsearchMapper $mapper;

    protected function setUp(): void
    {
        $this->query = new ElasticsearchBulkQuery(self::getClient(), $this->mapper = new ElasticsearchMapper(new CityIndex()));
    }

    protected function tearDown(): void
    {
        if (self::getClient()->hasIndex('test_cities')) {
            self::getClient()->deleteIndex('test_cities');
        }
    }

    /**
     *
     */
    public function test_index_bulk()
    {
        $response = $this->query
            ->into('test_cities')
            ->index([
                'name' => 'Paris',
                'zipCode' => '75001'
            ])
            ->index([
                'name' => 'Marseille',
                'zipCode' => '13001'
            ], fn (IndexOperation $op) => $op->id('foo'))
            ->refresh()
            ->execute()
        ;

        $this->assertFalse($response->isRead());
        $this->assertTrue($response->isWrite());
        $this->assertTrue($response->hasWrite());
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('took', $response);
        $this->assertArrayNotHasKey('foo', $response);
        $this->assertFalse($response['errors']);
        $this->assertCount(2, $response);
        $this->assertCount(2, $response->all());
        $this->assertEquals($response->all(), iterator_to_array($response));

        $this->assertEquals('test_cities', $response->all()[0]['index']['_index']);
        $this->assertEquals(1, $response->all()[0]['index']['_version']);
        $this->assertEquals('created', $response->all()[0]['index']['result']);
        $this->assertTrue($response->all()[0]['index']['forced_refresh']);
        $this->assertNotEmpty($response->all()[0]['index']['_id']);
        $this->assertEquals('test_cities', $response->all()[1]['index']['_index']);
        $this->assertEquals(1, $response->all()[1]['index']['_version']);
        $this->assertEquals('created', $response->all()[1]['index']['result']);
        $this->assertTrue($response->all()[1]['index']['forced_refresh']);
        $this->assertSame('foo', $response->all()[1]['index']['_id']);

        $this->assertEquals(2, $this->search()->execute()->total());
        $this->assertEquals([
            'name' => 'Paris',
            'zipCode' => '75001'
        ], $this->search()->where(new MatchBoolean('name', 'Paris'))->execute()['hits']['hits'][0]['_source']);
        $this->assertEquals([
            'name' => 'Marseille',
            'zipCode' => '13001'
        ], $this->search()->where(new MatchBoolean('name', 'Marseille'))->execute()['hits']['hits'][0]['_source']);
    }

    /**
     *
     */
    public function test_index_replace()
    {
        $this->query
            ->into('test_cities')
            ->index([
                '_id' => 'paris',
                'name' => 'Paris',
                'zipCode' => '75001'
            ])
            ->index([
                'name' => 'Marseille',
                'zipCode' => '13001'
            ], fn(IndexOperation $op) => $op->id('marseille'))
            ->refresh()
            ->execute()
        ;

        $this->assertEqualsCanonicalizing([
            [
                'name' => 'Marseille',
                'zipCode' => '13001'
            ],
            [
                'name' => 'Paris',
                'zipCode' => '75001'
            ],
        ], $this->search()->map(fn ($v) => $v['_source'])->all());

        $response = $this->query
            ->clear()
            ->index([
                '_id' => 'marseille',
                'name' => 'Marseille',
                'zipCode' => '13001',
                'population' => 861635,
            ])
            ->refresh()
            ->execute()
        ;

        $this->assertFalse($response->isRead());
        $this->assertTrue($response->isWrite());
        $this->assertTrue($response->hasWrite());
        $this->assertCount(1, $response);

        $this->assertEquals('test_cities', $response->all()[0]['index']['_index']);
        $this->assertEquals(2, $response->all()[0]['index']['_version']);
        $this->assertEquals('updated', $response->all()[0]['index']['result']);
        $this->assertTrue($response->all()[0]['index']['forced_refresh']);
        $this->assertEquals('marseille', $response->all()[0]['index']['_id']);

        $this->assertEqualsCanonicalizing([
            [
                'name' => 'Marseille',
                'zipCode' => '13001',
                'population' => 861635,
            ],
            [
                'name' => 'Paris',
                'zipCode' => '75001',
            ],
        ], $this->search()->map(fn ($v) => $v['_source'])->all());
    }

    /**
     *
     */
    public function test_index_with_entity()
    {
        $this->query
            ->into('test_cities')
            ->index(new City([
                'name' => 'Paris',
                'zipCode' => '75001'
            ]))
            ->index(new City([
                'name' => 'Marseille',
                'zipCode' => '13001'
            ]))
            ->refresh()
            ->execute()
        ;

        $this->assertEquals(2, $this->search()->execute()->total());
        $this->assertEquals([
            'name' => 'Paris',
            'zipCode' => '75001',
            'population' => null,
            'country' => null,
            'enabled' => true,
        ], $this->search()->where(new MatchBoolean('name', 'Paris'))->execute()['hits']['hits'][0]['_source']);
        $this->assertEquals([
            'name' => 'Marseille',
            'zipCode' => '13001',
            'population' => null,
            'country' => null,
            'enabled' => true,
        ], $this->search()->where(new MatchBoolean('name', 'Marseille'))->execute()['hits']['hits'][0]['_source']);
    }

    /**
     *
     */
    public function test_create()
    {
        $response = $this->query
            ->into('test_cities')
            ->create([
                'name' => 'Paris',
                'zipCode' => '75001'
            ])
            ->create([
                'name' => 'Marseille',
                'zipCode' => '13001'
            ], fn (CreateOperation $op) => $op->id('foo'))
            ->refresh()
            ->execute()
        ;

        $this->assertFalse($response->isRead());
        $this->assertTrue($response->isWrite());
        $this->assertTrue($response->hasWrite());
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('took', $response);
        $this->assertArrayNotHasKey('foo', $response);
        $this->assertFalse($response['errors']);
        $this->assertCount(2, $response);
        $this->assertCount(2, $response->all());
        $this->assertEquals($response->all(), iterator_to_array($response));

        $this->assertEquals('test_cities', $response->all()[0]['create']['_index']);
        $this->assertEquals(1, $response->all()[0]['create']['_version']);
        $this->assertEquals('created', $response->all()[0]['create']['result']);
        $this->assertTrue($response->all()[0]['create']['forced_refresh']);
        $this->assertNotEmpty($response->all()[0]['create']['_id']);
        $this->assertEquals('test_cities', $response->all()[1]['create']['_index']);
        $this->assertEquals(1, $response->all()[1]['create']['_version']);
        $this->assertEquals('created', $response->all()[1]['create']['result']);
        $this->assertTrue($response->all()[1]['create']['forced_refresh']);
        $this->assertSame('foo', $response->all()[1]['create']['_id']);

        $this->assertEquals(2, $this->search()->execute()->total());
        $this->assertEquals([
            'name' => 'Paris',
            'zipCode' => '75001'
        ], $this->search()->where(new MatchBoolean('name', 'Paris'))->execute()['hits']['hits'][0]['_source']);
        $this->assertEquals([
            'name' => 'Marseille',
            'zipCode' => '13001'
        ], $this->search()->where(new MatchBoolean('name', 'Marseille'))->execute()['hits']['hits'][0]['_source']);
    }

    /**
     *
     */
    public function test_create_with_id()
    {
        $response = $this->query
            ->into('test_cities')
            ->create([
                '_id' => '123',
                'name' => 'Paris',
                'zipCode' => '75001'
            ])
            ->create([
                '_id' => '456',
                'name' => 'Marseille',
                'zipCode' => '13001'
            ])
            ->refresh()
            ->execute()
        ;

        $this->assertEquals('123', $response->all()[0]['create']['_id']);
        $this->assertEquals('456', $response->all()[1]['create']['_id']);
    }

    /**
     *
     */
    public function test_create_with_entity()
    {
        $this->query
            ->into('test_cities')
            ->create(new City([
                'name' => 'Paris',
                'zipCode' => '75001'
            ]))
            ->create(new City([
                'name' => 'Marseille',
                'zipCode' => '13001'
            ]))
            ->refresh()
            ->execute()
        ;

        $this->assertEquals(2, $this->search()->execute()->total());
        $this->assertEquals([
            'name' => 'Paris',
            'zipCode' => '75001',
            'population' => null,
            'country' => null,
            'enabled' => true,
        ], $this->search()->where(new MatchBoolean('name', 'Paris'))->execute()['hits']['hits'][0]['_source']);
        $this->assertEquals([
            'name' => 'Marseille',
            'zipCode' => '13001',
            'population' => null,
            'country' => null,
            'enabled' => true,
        ], $this->search()->where(new MatchBoolean('name', 'Marseille'))->execute()['hits']['hits'][0]['_source']);
    }

    /**
     *
     */
    public function test_delete()
    {
        $this->query
            ->into('test_cities')
            ->index([
                '_id' => 1,
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ])
            ->index([
                '_id' => 2,
                'name' => 'Paris',
                'population' => 27022,
                'country' => 'US'
            ])
            ->index([
                '_id' => 3,
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR'
            ])
            ->index([
                '_id' => 4,
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ])
            ->refresh()
            ->execute()
        ;

        $this->query
            ->clear()
            ->delete(2)
            ->delete(4)
            ->refresh()
            ->execute()
        ;

        $this->assertEqualsCanonicalizing([
            [
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ],
            [
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR'
            ],
        ], $this->search()->map(fn ($v) => $v['_source'])->all());
    }

    /**
     *
     */
    public function test_update_with_entity()
    {
        $this->query
            ->into('test_cities')
            ->index(new City([
                'id' => 1,
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ]))
            ->index(new City([
                'id' => 2,
                'name' => 'Paris',
                'population' => 27022,
                'country' => 'US'
            ]))
            ->index(new City([
                'id' => 3,
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR'
            ]))
            ->index(new City([
                'id' => 4,
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ]))
            ->refresh()
            ->execute()
        ;

        $this->query
            ->clear()
            ->update(new City([
                'id' => 2,
                'name' => 'Paris',
                'population' => 25001,
                'country' => 'US'
            ]))
            ->update(new City([
                'id' => 4,
                'name' => 'Cavaillon',
                'population' => 32000,
                'country' => 'FR'
            ]))
            ->refresh()
            ->execute()
        ;

        $this->assertEqualsCanonicalizing([
            [
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR',
                'zipCode' => null,
                'enabled' => true,
            ],
            [
                'name' => 'Paris',
                'population' => 25001,
                'country' => 'US',
                'zipCode' => null,
                'enabled' => true,
            ],
            [
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR',
                'zipCode' => null,
                'enabled' => true,
            ],
            [
                'name' => 'Cavaillon',
                'population' => 32000,
                'country' => 'FR',
                'zipCode' => null,
                'enabled' => true,
            ]
        ], $this->search()->map(fn ($v) => $v['_source'])->all());
    }

    /**
     *
     */
    public function test_update_with_script()
    {
        $this->query
            ->into('test_cities')
            ->index(new City([
                'id' => 1,
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ]))
            ->index(new City([
                'id' => 2,
                'name' => 'Paris',
                'population' => 27022,
                'country' => 'US'
            ]))
            ->index(new City([
                'id' => 3,
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR'
            ]))
            ->index(new City([
                'id' => 4,
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ]))
            ->refresh()
            ->execute()
        ;

        $this->query
            ->clear()
            ->update(fn (UpdateOperation $op) => $op->id(3)->script('ctx._source.population += 1000'))
            ->update(fn (UpdateOperation $op) => $op->id(4)->script('ctx._source.population += 1000'))
            ->refresh()
            ->execute()
        ;

        $this->assertEqualsCanonicalizing([
            [
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR',
                'zipCode' => null,
                'enabled' => true,
            ],
            [
                'name' => 'Paris',
                'population' => 27022,
                'country' => 'US',
                'zipCode' => null,
                'enabled' => true,
            ],
            [
                'name' => 'Parthenay',
                'population' => 12599,
                'country' => 'FR',
                'zipCode' => null,
                'enabled' => true,
            ],
            [
                'name' => 'Cavaillon',
                'population' => 27689,
                'country' => 'FR',
                'zipCode' => null,
                'enabled' => true,
            ]
        ], $this->search()->map(fn ($v) => $v['_source'])->all());
    }

    /**
     *
     */
    public function test_update_upsert()
    {
        $this->query
            ->into('test_cities')
            ->index(new City([
                'id' => 1,
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ]))
            ->index(new City([
                'id' => 2,
                'name' => 'Paris',
                'population' => 27022,
                'country' => 'US'
            ]))
            ->index(new City([
                'id' => 3,
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR'
            ]))
            ->refresh()
            ->execute()
        ;

        $this->query
            ->clear()
            ->update(new City([
                'id' => 2,
                'name' => 'Paris',
                'population' => 25001,
                'country' => 'US'
            ]), fn (UpdateOperation $op) => $op->upsert())
            ->update(new City([
                'id' => 4,
                'name' => 'Cavaillon',
                'population' => 32000,
                'country' => 'FR'
            ]), fn (UpdateOperation $op) => $op->upsert())
            ->refresh()
            ->execute()
        ;

        $this->assertEqualsCanonicalizing([
            [
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR',
                'zipCode' => null,
                'enabled' => true,
            ],
            [
                'name' => 'Paris',
                'population' => 25001,
                'country' => 'US',
                'zipCode' => null,
                'enabled' => true,
            ],
            [
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR',
                'zipCode' => null,
                'enabled' => true,
            ],
            [
                'name' => 'Cavaillon',
                'population' => 32000,
                'country' => 'FR',
                'zipCode' => null,
                'enabled' => true,
            ]
        ], $this->search()->map(fn ($v) => $v['_source'])->all());
    }

    /**
     *
     */
    public function test_update_upsert_with_script()
    {
        $this->query
            ->into('test_cities')
            ->index(new City([
                'id' => 1,
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ]))
            ->index(new City([
                'id' => 2,
                'name' => 'Paris',
                'population' => 27022,
                'country' => 'US'
            ]))
            ->index(new City([
                'id' => 3,
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR'
            ]))
            ->refresh()
            ->execute()
        ;

        $this->query
            ->clear()
            ->update(fn (UpdateOperation $op) => $op
                ->script('ctx._source.population += 10000')
                ->upsert(new City([
                    'id' => 2,
                    'name' => 'Paris',
                    'population' => 25001,
                    'country' => 'US'
                ]))
            )
            ->update(fn (UpdateOperation $op) => $op
                ->script('ctx._source.population += 10000')
                ->upsert(new City([
                    'id' => 4,
                    'name' => 'Cavaillon',
                    'population' => 32000,
                    'country' => 'FR'
                ]))
            )
            ->refresh()
            ->execute()
        ;

        $this->assertEqualsCanonicalizing([
            [
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR',
                'zipCode' => null,
                'enabled' => true,
            ],
            [
                'name' => 'Paris',
                'population' => 37022,
                'country' => 'US',
                'zipCode' => null,
                'enabled' => true,
            ],
            [
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR',
                'zipCode' => null,
                'enabled' => true,
            ],
            [
                'name' => 'Cavaillon',
                'population' => 32000,
                'country' => 'FR',
                'zipCode' => null,
                'enabled' => true,
            ]
        ], $this->search()->map(fn ($v) => $v['_source'])->all());
    }

    public function test_count()
    {
        $this->query
            ->into('test_cities')
            ->index(new City([
                'id' => 1,
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ]))
            ->index(new City([
                'id' => 2,
                'name' => 'Paris',
                'population' => 27022,
                'country' => 'US'
            ]))
            ->index(new City([
                'id' => 3,
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR'
            ]))
        ;

        $this->assertCount(3, $this->query);
        $this->assertCount(0, $this->query->clear());
    }

    public function search(): ElasticsearchQuery
    {
        return (new ElasticsearchQuery(self::getClient()))->from('test_cities');
    }
}
