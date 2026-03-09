<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapper;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\MatchBoolean;
use Bdf\Prime\Indexer\Exception\QueryExecutionException;
use Bdf\Prime\Indexer\IndexTestCase;

use ElasticsearchTestFiles\City;
use ElasticsearchTestFiles\CityIndex;

use function array_map;

class ElasticsearchMultiSearchQueryTest extends IndexTestCase
{
    /**
     * @var ElasticsearchMultiSearchQuery
     */
    private $query;

    protected function setUp(): void
    {
        $this->query = new ElasticsearchMultiSearchQuery(self::getClient(), 'test_cities');
    }

    protected function tearDown(): void
    {
        if (self::getClient()->hasIndex('test_cities')) {
            self::getClient()->deleteIndex('test_cities');
        }
    }

    public function test_execute()
    {
        $create = new ElasticsearchCreateQuery(self::getClient());
        $create
            ->into('test_cities')
            ->values([
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'New York',
                'population' => 8175133,
                'country' => 'US'
            ])
            ->values([
                'name' => 'Los Angeles',
                'population' => 3792621,
                'country' => 'US'
            ])
            ->refresh()
            ->execute()
        ;

        $frQuery = (new ElasticsearchQuery(self::getClient()))->from('test_cities')->filter(new MatchBoolean('country', 'FR'));
        $usQuery = (new ElasticsearchQuery(self::getClient()))->from('test_cities')->filter(new MatchBoolean('country', 'US'));

        $results = $this->query
            ->push($frQuery, 'fr')
            ->push($usQuery, 'us')
            ->execute()
        ;

        $this->assertCount(2, $results);
        $this->assertArrayHasKey('fr', $results);
        $this->assertArrayHasKey('us', $results);

        $this->assertSame(
            [
                [
                    'name' => 'Paris',
                    'population' => 2201578,
                    'country' => 'FR'
                ],
                [
                    'name' => 'Cavaillon',
                    'population' => 26689,
                    'country' => 'FR'
                ]
            ],
            array_map(fn ($hit) => $hit['_source'], $results['fr']->hits())
        );

        $this->assertSame(
            [
                [
                    'name' => 'New York',
                    'population' => 8175133,
                    'country' => 'US'
                ],
                [
                    'name' => 'Los Angeles',
                    'population' => 3792621,
                    'country' => 'US'
                ]
            ],
            array_map(fn ($hit) => $hit['_source'], $results['us']->hits())
        );
    }

    public function test_execute_error()
    {
        $this->expectException(QueryExecutionException::class);
        $this->expectExceptionMessage('no such index [test_cities]');

        $this->query->query('fr')->filter(new MatchBoolean('#####', 'FR'));
        $this->query->query('use')->filter(new MatchBoolean('#####', 'US'));

        $this->query->execute();
    }

    public function test_count()
    {
        $create = new ElasticsearchCreateQuery(self::getClient());
        $create
            ->into('test_cities')
            ->values([
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'New York',
                'population' => 8175133,
                'country' => 'US'
            ])
            ->values([
                'name' => 'Los Angeles',
                'population' => 3792621,
                'country' => 'US'
            ])
            ->refresh()
            ->execute()
        ;

        $frQuery = (new ElasticsearchQuery(self::getClient()))->from('test_cities')->filter(new MatchBoolean('country', 'FR'));
        $usQuery = (new ElasticsearchQuery(self::getClient()))->from('test_cities')->filter(new MatchBoolean('country', 'US'));

        $results = $this->query
            ->push($frQuery, 'fr')
            ->push($usQuery, 'us')
            ->count()
        ;

        $this->assertSame([
            'fr' => 2,
            'us' => 2,
        ], $results);
    }

    public function test_count_error()
    {
        $this->expectException(QueryExecutionException::class);
        $this->expectExceptionMessage('no such index [test_cities]');

        $frQuery = (new ElasticsearchQuery(self::getClient()))->from('test_cities')->filter(new MatchBoolean('country', 'FR'));
        $usQuery = (new ElasticsearchQuery(self::getClient()))->from('test_cities')->filter(new MatchBoolean('country', 'US'));

        $this->query
            ->push($frQuery, 'fr')
            ->push($usQuery, 'us')
            ->count()
        ;
    }

    public function test_query_and_count()
    {
        $create = new ElasticsearchCreateQuery(self::getClient());
        $create
            ->into('test_cities')
            ->values([
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'New York',
                'population' => 8175133,
                'country' => 'US'
            ])
            ->values([
                'name' => 'Los Angeles',
                'population' => 3792621,
                'country' => 'US'
            ])
            ->refresh()
            ->execute()
        ;

        $this->query->query('fr')->filter(new MatchBoolean('country', 'FR'));
        $this->query->query('us')->filter(new MatchBoolean('country', 'US'));
        $results = $this->query->count();

        $this->assertSame(['fr' => 2, 'us' => 2], $results);
    }

    public function test_first()
    {
        $create = new ElasticsearchCreateQuery(self::getClient());
        $create
            ->into('test_cities')
            ->values([
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'New York',
                'population' => 8175133,
                'country' => 'US'
            ])
            ->values([
                'name' => 'Los Angeles',
                'population' => 3792621,
                'country' => 'US'
            ])
            ->refresh()
            ->execute()
        ;

        $frQuery = (new ElasticsearchQuery(self::getClient()))->from('test_cities')->filter(new MatchBoolean('country', 'FR'))->order('population', 'desc');
        $usQuery = (new ElasticsearchQuery(self::getClient()))->from('test_cities')->filter(new MatchBoolean('country', 'US'))->order('population', 'desc');

        $results = $this->query
            ->push($frQuery, 'fr')
            ->push($usQuery, 'us')
            ->map(fn (array $doc) => $doc['_source'])
            ->first()
        ;

        $this->assertSame(
            [
                'fr' => [
                    'name' => 'Paris',
                    'population' => 2201578,
                    'country' => 'FR'
                ],
                'us' => [
                    'name' => 'New York',
                    'population' => 8175133,
                    'country' => 'US'
                ],
            ],
            $results
        );
    }

    public function test_first_with_missing_queries()
    {
        $create = new ElasticsearchCreateQuery(self::getClient());
        $create
            ->into('test_cities')
            ->values([
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'New York',
                'population' => 8175133,
                'country' => 'US'
            ])
            ->values([
                'name' => 'Los Angeles',
                'population' => 3792621,
                'country' => 'US'
            ])
            ->refresh()
            ->execute()
        ;

        $frQuery = (new ElasticsearchQuery(self::getClient()))->from('test_cities')->filter(new MatchBoolean('country', 'FR'))->filter('population', '<', 1000000);
        $usQuery = (new ElasticsearchQuery(self::getClient()))->from('test_cities')->filter(new MatchBoolean('country', 'US'))->filter('population', '<', 1000000);

        $results = $this->query
            ->push($frQuery, 'fr')
            ->push($usQuery, 'us')
            ->map(fn (array $doc) => $doc['_source'])
            ->first()
        ;

        $this->assertSame(
            [
                'fr' => [
                    'name' => 'Cavaillon',
                    'population' => 26689,
                    'country' => 'FR'
                ],
            ],
            $results
        );
    }

    public function test_all()
    {
        $create = new ElasticsearchCreateQuery(self::getClient());
        $create
            ->into('test_cities')
            ->values([
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'New York',
                'population' => 8175133,
                'country' => 'US'
            ])
            ->values([
                'name' => 'Los Angeles',
                'population' => 3792621,
                'country' => 'US'
            ])
            ->refresh()
            ->execute()
        ;

        $frQuery = (new ElasticsearchQuery(self::getClient()))->from('test_cities')->filter(new MatchBoolean('country', 'FR'))->order('population', 'desc');
        $usQuery = (new ElasticsearchQuery(self::getClient()))->from('test_cities')->filter(new MatchBoolean('country', 'US'))->order('population', 'desc');

        $results = $this->query
            ->push($frQuery, 'fr')
            ->push($usQuery, 'us')
            ->map(fn (array $doc) => $doc['_source'])
            ->all()
        ;

        $this->assertSame(
            [
                'fr' => [
                    [
                        'name' => 'Paris',
                        'population' => 2201578,
                        'country' => 'FR'
                    ],
                    [
                        'name' => 'Cavaillon',
                        'population' => 26689,
                        'country' => 'FR'
                    ],
                ],
                'us' => [
                    [
                        'name' => 'New York',
                        'population' => 8175133,
                        'country' => 'US'
                    ],
                    [
                        'name' => 'Los Angeles',
                        'population' => 3792621,
                        'country' => 'US'
                    ],
                ],
            ],
            $results
        );
    }

    public function test_all_with_mapper()
    {
        $query = new ElasticsearchMultiSearchQuery(self::getClient(), 'test_cities', new ElasticsearchMapper(new CityIndex()));
        $create = new ElasticsearchCreateQuery(self::getClient());
        $create
            ->into('test_cities')
            ->values([
                '_id' => '1',
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ])
            ->values([
                '_id' => '2',
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ])
            ->values([
                '_id' => '3',
                'name' => 'New York',
                'population' => 8175133,
                'country' => 'US'
            ])
            ->values([
                '_id' => '4',
                'name' => 'Los Angeles',
                'population' => 3792621,
                'country' => 'US'
            ])
            ->refresh()
            ->execute()
        ;

        $query->query('fr', false)->filter(new MatchBoolean('country', 'FR'))->order('population', 'desc');
        $query->query('us', false)->filter(new MatchBoolean('country', 'US'))->order('population', 'desc');

        $results = $query->all();

        $this->assertEquals(
            [
                'fr' => [
                    new City([
                        'id' => '1',
                        'name' => 'Paris',
                        'population' => 2201578,
                        'country' => 'FR'
                    ]),
                    new City([
                        'id' => '2',
                        'name' => 'Cavaillon',
                        'population' => 26689,
                        'country' => 'FR'
                    ]),
                ],
                'us' => [
                    new City([
                        'id' => '3',
                        'name' => 'New York',
                        'population' => 8175133,
                        'country' => 'US'
                    ]),
                    new City([
                        'id' => '4',
                        'name' => 'Los Angeles',
                        'population' => 3792621,
                        'country' => 'US'
                    ]),
                ],
            ],
            $results
        );
    }

    public function test_all_with_mapper_default_scope()
    {
        $query = new ElasticsearchMultiSearchQuery(self::getClient(), 'test_cities', new ElasticsearchMapper(new CityIndex()));
        $create = new ElasticsearchCreateQuery(self::getClient());
        $create
            ->into('test_cities')
            ->values([
                '_id' => '1',
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR',
                'enabled' => true,
            ])
            ->values([
                '_id' => '2',
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ])
            ->values([
                '_id' => '3',
                'name' => 'New York',
                'population' => 8175133,
                'country' => 'US'
            ])
            ->values([
                '_id' => '4',
                'name' => 'Los Angeles',
                'population' => 3792621,
                'country' => 'US',
                'enabled' => true,
            ])
            ->refresh()
            ->execute()
        ;

        $query->query('fr')->filter(new MatchBoolean('country', 'FR'))->order('population', 'desc');
        $query->query('us')->filter(new MatchBoolean('country', 'US'))->order('population', 'desc');

        $results = $query->all();

        $this->assertEquals(
            [
                'fr' => [
                    new City([
                        'id' => '1',
                        'name' => 'Paris',
                        'population' => 2201578,
                        'country' => 'FR'
                    ]),
                ],
                'us' => [
                    new City([
                        'id' => '4',
                        'name' => 'Los Angeles',
                        'population' => 3792621,
                        'country' => 'US'
                    ]),
                ],
            ],
            $results
        );
    }
}
