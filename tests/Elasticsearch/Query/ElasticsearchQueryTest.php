<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query;

use Bdf\Collection\Stream\ArrayStream;
use Bdf\Collection\Util\Optional;
use Bdf\Prime\Indexer\Elasticsearch\Query\Compound\FunctionScoreQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\MatchBoolean;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\MatchPhrase;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\QueryString;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\Range;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\Wildcard;
use Bdf\Prime\Indexer\Elasticsearch\Query\Result\ElasticsearchPaginator;
use Bdf\Prime\Indexer\Exception\QueryExecutionException;
use Bdf\Prime\Indexer\IndexTestCase;
use Elastic\Elasticsearch\Client;

/**
 * Class ElasticsearchQueryTest
 */
class ElasticsearchQueryTest extends IndexTestCase
{
    /**
     * @var ElasticsearchQuery
     */
    private $query;

    protected function setUp(): void
    {
        $this->query = new ElasticsearchQuery(self::getClient());
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
    public function test_compile_empty()
    {
        $this->assertEquals([], $this->query->compile());
    }

    /**
     *
     */
    public function test_simple_where()
    {
        $this->assertEquals(
            [
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['term' => ['name' => 'Paris']]
                        ]
                    ]
                ]
            ],
            $this->query->from('cities', 'city')->where('name', 'Paris')->compile()
        );
    }

    /**
     *
     */
    public function test_where_null()
    {
        $this->assertEquals(
            [
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['missing' => ['field' => 'name']]
                        ]
                    ]
                ]
            ],
            $this->query->from('cities', 'city')->where('name', null)->compile()
        );
    }

    /**
     *
     */
    public function test_with_with_custom_filters()
    {
        $this->query = new ElasticsearchQuery(
            self::getClient(),
            [
                'search' => function (ElasticsearchQuery $query, $value) {
                    $query
                        ->should('name', $value)
                        ->should('zipCode', $value)
                    ;
                }
            ]
        );

        $this->assertEquals(
            [
                'query' => [
                    'bool' => [
                        'minimum_should_match' => 1,
                        'should' => [
                            ['term' => ['name' => 'Paris']],
                            ['term' => ['zipCode' => 'Paris']]
                        ]
                    ]
                ]
            ],
            $this->query->from('cities', 'city')->where('search', 'Paris')->compile()
        );
    }

    /**
     *
     */
    public function test_orWhere()
    {
        $this->assertEquals(
            [
                'query' => [
                    'bool' => [
                        'minimum_should_match' => 1,
                        'should' => [
                            ['term' => ['name' => 'Paris']],
                            ['term' => ['zipCode' => '75000']],
                        ]
                    ]
                ]
            ],
            $this->query
                ->from('cities', 'city')
                ->where('name', 'Paris')
                ->orWhere('zipCode', '75000')
                ->compile()
        );
    }

    /**
     *
     */
    public function test_where_multiple()
    {
        $this->assertEquals(
            [
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['term' => ['name' => 'Paris']],
                            ['term' => ['zipCode' => '75000']],
                        ]
                    ]
                ]
            ],
            $this->query
                ->from('cities', 'city')
                ->where('name', 'Paris')
                ->where('zipCode', '75000')
                ->compile()
        );
    }

    /**
     *
     */
    public function test_where_with_array()
    {
        $this->assertEquals(
            [
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['term' => ['name' => 'Paris']],
                            ['term' => ['zipCode' => '75000']],
                        ]
                    ]
                ]
            ],
            $this->query
                ->from('cities', 'city')
                ->where([
                    'name' => 'Paris',
                    'zipCode' => '75000',
                ])
                ->compile()
        );
    }

    /**
     *
     */
    public function test_where_with_array_and_custom_filter()
    {
        $this->query = new ElasticsearchQuery(
            self::getClient(),
            [
                'search' => function (ElasticsearchQuery $query, $value) {
                    $query
                        ->should('name', $value)
                        ->should('zipCode', $value)
                    ;
                }
            ]
        );

        $this->assertEquals(
            [
                'query' => [
                    'bool' => [
                        'minimum_should_match' => 1,
                        'should' => [
                            ['term' => ['name' => 'Paris']],
                            ['term' => ['zipCode' => 'Paris']],
                        ],
                        'filter' => [
                            ['term' => ['country' => 'FR']]
                        ]
                    ]
                ]
            ],
            $this->query
                ->from('cities', 'city')
                ->where([
                    'search' => 'Paris',
                    'country' => 'FR'
                ])
                ->compile()
        );
    }

    /**
     *
     */
    public function test_where_with_array_and_raw_filter()
    {
        $this->query = new ElasticsearchQuery(
            self::getClient(),
            [
                'search' => function (ElasticsearchQuery $query, $value) {
                    $query
                        ->should('name', $value)
                        ->should('zipCode', $value)
                    ;
                }
            ]
        );

        $this->assertEquals(
            [
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['range' => ['population' => ['gt' => 10000]]],
                            ['prefix' => ['zipCode' => '75']],
                        ]
                    ]
                ]
            ],
            $this->query
                ->from('cities', 'city')
                ->where([
                    (new Range('population'))->gt(10000),
                    new Wildcard('zipCode', '75*')
                ])
                ->compile()
        );
    }

    /**
     *
     */
    public function test_where_nested()
    {
        $this->assertEquals(
            [
                'query' => [
                    'bool' => [
                        'minimum_should_match' => 1,
                        'should' => [
                            [
                                'bool' => [
                                    'filter' => [
                                        ['term' => ['name' => 'Paris']],
                                        ['term' => ['zipCode' => '75000']],
                                    ]
                                ]
                            ],
                            [
                                'bool' => [
                                    'filter' => [
                                        ['prefix' => ['name' => 'P']],
                                        ['prefix' => ['zipCode' => '75']],
                                    ]
                                ]
                            ],
                        ]
                    ]
                ]
            ],
            $this->query
                ->from('cities', 'city')
                ->where(function (ElasticsearchQuery $query) {
                    $query
                        ->where('name', 'Paris')
                        ->where('zipCode', '75000')
                    ;
                })
                ->orWhere(function (ElasticsearchQuery $query) {
                    $query
                        ->where('name', ':like', 'P%')
                        ->where('zipCode', ':like', '75%')
                    ;
                })
                ->compile()
        );
    }

    /**
     *
     */
    public function test_filter()
    {
        $this->assertEquals(
            [
                'query' => [
                    'bool' => [
                        'minimum_should_match' => 1,
                        'filter' => [['term' => ['country' => 'FR']]],
                        'should' => [
                            ['term' => ['name' => 'Paris']],
                            ['term' => ['zipCode' => '75000']],
                        ]
                    ]
                ]
            ],
            $this->query
                ->from('cities', 'city')
                ->where('name', 'Paris')
                ->orWhere('zipCode', '75000')
                ->filter('country', 'FR')
                ->compile()
        );
    }

    /**
     *
     */
    public function test_should()
    {
        $this->assertEquals(
            [
                'query' => [
                    'bool' => [
                        'minimum_should_match' => 1,
                        'filter' => [
                            ['term' => ['name' => 'Paris']]
                        ],
                        'should' => [
                            ['term' => ['country' => 'FR']],
                        ]
                    ]
                ]
            ],
            $this->query
                ->from('cities', 'city')
                ->where('name', 'Paris')
                ->should('country', 'FR')
                ->compile()
        );
    }

    /**
     *
     */
    public function test_must()
    {
        $this->assertEquals(
            [
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['term' => ['name' => 'Paris']]
                        ],
                        'must' => [
                            ['term' => ['country' => 'FR']],
                        ]
                    ]
                ]
            ],
            $this->query
                ->from('cities', 'city')
                ->where('name', 'Paris')
                ->must('country', 'FR')
                ->compile()
        );
    }

    /**
     *
     */
    public function test_mustNot()
    {
        $this->assertEquals(
            [
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['term' => ['name' => 'Paris']]
                        ],
                        'must_not' => [
                            ['term' => ['country' => 'FR']],
                        ]
                    ]
                ]
            ],
            $this->query
                ->from('cities', 'city')
                ->where('name', 'Paris')
                ->mustNot('country', 'FR')
                ->compile()
        );
    }

    /**
     *
     */
    public function test_whereNull()
    {
        $this->assertEquals(
            [
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['missing' => ['field' => 'population']]
                        ]
                    ]
                ]
            ],
            $this->query->from('cities', 'city')->whereNull('population')->compile()
        );
    }

    /**
     *
     */
    public function test_whereNotNull()
    {
        $this->assertEquals(
            [
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['exists' => ['field' => 'population']]
                        ]
                    ]
                ]
            ],
            $this->query->from('cities', 'city')->whereNotNull('population')->compile()
        );
    }

    /**
     *
     */
    public function test_orWhereNull()
    {
        $this->assertEquals(
            [
                'query' => [
                    'bool' => [
                        'minimum_should_match' => 1,
                        'should' => [
                            ['range' => ['population' => ['gt' => 10000]]],
                            ['missing' => ['field' => 'population']]
                        ]
                    ]
                ]
            ],
            $this->query->from('cities', 'city')
                ->where('population', '>', 10000)
                ->orWhereNull('population')
                ->compile()
        );
    }

    /**
     *
     */
    public function test_orWhereNotNull()
    {
        $this->assertEquals(
            [
                'query' => [
                    'bool' => [
                        'minimum_should_match' => 1,
                        'should' => [
                            ['range' => ['population' => ['gt' => 10000]]],
                            ['exists' => ['field' => 'population']]
                        ]
                    ]
                ]
            ],
            $this->query->from('cities', 'city')
                ->where('population', '>', 10000)
                ->orWhereNotNull('population')
                ->compile()
        );
    }

    /**
     *
     */
    public function test_wrap()
    {
        $compiled = $this->query->from('cities', 'city')
            ->wrap(
                (new FunctionScoreQuery())
                    ->addFunction('field_value_factor', [
                        'field' => 'population',
                        'factor' => 1,
                        'modifier' => 'log1p'
                    ])
                    ->scoreMode('multiply')
            )
            ->should(function (ElasticsearchQuery $query) {
                $query
                    ->whereRaw(
                        (new QueryString('par%'))
                            ->and()
                            ->defaultField('name')
                            ->analyzeWildcard()
                            ->useLikeSyntax()
                    )
                    ->orWhereRaw(new MatchPhrase('name', 'par'))
                ;
            })
            ->filter(new MatchBoolean('country', 'FR'))
            ->compile()
        ;

        $this->assertEquals([
            'query' => [
                'function_score' => [
                    'score_mode' => 'multiply',
                    'field_value_factor' => [
                        'field' => 'population',
                        'factor' => 1,
                        'modifier' => 'log1p'
                    ],
                    'query' => [
                        'bool' => [
                            'minimum_should_match' => 1,
                            'filter' => [['match' => ['country' => 'FR']]],
                            'should' => [
                                ['query_string' => [
                                    'query' => 'par*',
                                    'default_operator' => 'AND',
                                    'default_field' => 'name',
                                    'analyze_wildcard' => true,
                                ]],
                                ['match_phrase' => ['name' => 'par']]
                            ]
                        ]
                    ],
                ]
            ]
        ], $compiled);
    }

    /**
     *
     */
    public function test_order()
    {
        $this->assertEquals([
            'sort' => [
                ['name' => 'asc']
            ]
        ], $this->query->order('name')->compile());

        $this->assertEquals([
            'sort' => [
                ['name' => 'asc'],
                ['date' => 'desc'],
            ]
        ], $this->query->addOrder('date', 'desc')->compile());

        $this->assertEquals([
            'sort' => [
                ['firstName' => 'asc'],
                ['lastName' => 'desc'],
            ]
        ], $this->query->order(['firstName' => 'asc', 'lastName' => 'desc'])->compile());

        $this->assertEquals([
            'sort' => [
                ['firstName' => 'desc'],
                ['lastName' => 'desc'],
                ['age' => 'desc'],
            ]
        ], $this->query->addOrder(['firstName' => 'desc', 'age' => 'desc'])->compile());

        $this->assertSame([
            'firstName' => 'desc',
            'lastName' => 'desc',
            'age' => 'desc',
        ], $this->query->getOrders());
    }

    /**
     *
     */
    public function test_limit_compile()
    {
        $this->assertEquals([
            'size' => 5
        ], $this->query->limit(5)->compile());

        $this->query->limit(5, 15);

        $this->assertEquals([
            'size' => 5,
            'from' => 15
        ], $this->query->limit(5, 15)->compile());

        $this->assertEquals([
            'size' => 5,
            'from' => 10
        ], $this->query->offset(10)->compile());
        $this->assertEquals([
            'size' => 20,
            'from' => 40
        ], $this->query->limitPage(3, 20)->compile());
    }

    /**
     *
     */
    public function test_limit_getters()
    {
        $this->assertEquals(1, $this->query->getPage());
        $this->assertNull($this->query->getLimit());
        $this->assertNull($this->query->getOffset());
        $this->assertFalse($this->query->isLimitQuery());
        $this->assertFalse($this->query->hasPagination());

        $this->query->limit(5);

        $this->assertEquals(1, $this->query->getPage());
        $this->assertEquals(5, $this->query->getLimit());
        $this->assertNull($this->query->getOffset());
        $this->assertTrue($this->query->isLimitQuery());
        $this->assertFalse($this->query->hasPagination());

        $this->query->offset(15);

        $this->assertSame(4, $this->query->getPage());
        $this->assertSame(5, $this->query->getLimit());
        $this->assertSame(15, $this->query->getOffset());
        $this->assertTrue($this->query->isLimitQuery());
        $this->assertTrue($this->query->hasPagination());
    }

    /**
     *
     */
    public function test_limit_functional()
    {
        $create = new ElasticsearchCreateQuery(self::getClient());
        $create
            ->into('test_cities', 'city')
            ->values([
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Paris',
                'population' => 27022,
                'country' => 'US'
            ])
            ->values([
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ])
            ->refresh()
            ->execute()
        ;

        $query = $this->query
            ->from('test_cities', 'city')
            ->order('population', 'desc')
            ->map(function ($doc) { return $doc['_source']['name']; })
        ;

        $this->assertEquals(['Paris', 'Paris'], $query->limit(2)->all());
        $this->assertEquals(['Cavaillon', 'Parthenay'], $query->limit(2, 2)->all());
        $this->assertEquals([], $query->limit(2, 4)->all());
    }

    /**
     *
     */
    public function test_order_functional()
    {
        $create = new ElasticsearchCreateQuery(self::getClient());
        $create
            ->into('test_cities', 'city')
            ->values([
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Paris',
                'population' => 27022,
                'country' => 'US'
            ])
            ->values([
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ])
            ->refresh()
            ->execute()
        ;

        $query = $this->query
            ->from('test_cities')
            ->order('population', 'asc')
            ->map(function ($doc) { return $doc['_source']['name'] . '-' . $doc['_source']['country']; })
        ;

        $this->assertEquals([
            'Parthenay-FR',
            'Cavaillon-FR',
            'Paris-US',
            'Paris-FR',
        ], $query->all());
    }

    /**
     *
     */
    public function test_execute_functional()
    {
        $create = new ElasticsearchCreateQuery(self::getClient());
        $create
            ->into('test_cities', 'city')
            ->values([
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Paris',
                'population' => 27022,
                'country' => 'US'
            ])
            ->values([
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ])
            ->refresh()
            ->execute()
        ;

        $response = $this->query->from('test_cities', 'city')
            ->wrap(
                (new FunctionScoreQuery())
                    ->addFunction('field_value_factor', [
                        'field' => 'population',
                        'factor' => 1,
                        'modifier' => 'log1p'
                    ])
                    ->scoreMode('multiply')
            )
            ->should(function (ElasticsearchQuery $query) {
                $query
                    ->whereRaw(
                        (new QueryString('par%'))
                            ->and()
                            ->defaultField('name')
                            ->analyzeWildcard()
                            ->useLikeSyntax()
                    )
                    ->orWhereRaw(new MatchPhrase('name', 'par'))
                ;
            })
            ->filter(new MatchBoolean('country', 'FR'))
            ->execute()
        ;

        $this->assertEquals(2, $response->total());
        $this->assertEquals([
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR'
        ], $response->hits()[0]['_source']);
        $this->assertEquals([
            'name' => 'Parthenay',
            'population' => 11599,
            'country' => 'FR'
        ], $response->hits()[1]['_source']);
    }

    /**
     *
     */
    public function test_stream_functional()
    {
        $create = new ElasticsearchCreateQuery(self::getClient());
        $create
            ->into('test_cities', 'city')
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
            ->execute()
        ;

        sleep(2);

        $stream = $this->query->from('test_cities', 'city')->order('population')->stream();

        $this->assertInstanceOf(ArrayStream::class, $stream);

        $this->assertCount(2, $stream);
        $this->assertEquals([
            [
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ],
            [
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ],
        ], $stream->map(function (array $data) { return $data['_source']; })->toArray());
    }

    /**
     *
     */
    public function test_all_functional()
    {
        $create = new ElasticsearchCreateQuery(self::getClient());
        $create
            ->into('test_cities', 'city')
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
            ->execute()
        ;

        sleep(2);

        $this->assertEquals([
            [
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ],
            [
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ],
        ], $this->query->from('test_cities', 'city')->order('population')->map(function (array $data) { return $data['_source']; })->all());
    }

    /**
     *
     */
    public function test_first_functional()
    {
        $create = new ElasticsearchCreateQuery(self::getClient());
        $create
            ->into('test_cities', 'city')
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
            ->execute()
        ;

        sleep(2);

        $this->assertEquals(
            Optional::of([
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ])
            , $this->query->from('test_cities', 'city')->order('population')->map(function (array $data) { return $data['_source']; })->first()
        );
        $this->assertEquals(Optional::empty() , $this->query->from('test_cities', 'city')->where('name', 'not_found')->first());
    }

    /**
     *
     */
    public function test_count_functional()
    {
        $create = new ElasticsearchCreateQuery(self::getClient());
        $create
            ->into('test_cities', 'city')
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
            ->refresh()
            ->execute()
        ;

        $this->assertSame(2, $this->query->from('test_cities', 'city')->count());
        $this->assertCount(2, $this->query->from('test_cities', 'city'));
        $this->assertCount(1, $this->query->from('test_cities', 'city')->where('population', '>', 1000000));
        $this->assertCount(0, $this->query->from('test_cities', 'city')->where('population', '>', 1000000000));
    }

    /**
     *
     */
    public function test_count_functional_error()
    {
        $this->expectException(QueryExecutionException::class);
        $this->expectExceptionMessage('no such index [not_found]');

        $this->query->from('not_found')->count();
    }

    /**
     *
     */
    public function test_paginate_functional()
    {
        $create = new ElasticsearchCreateQuery(self::getClient());
        $create
            ->into('test_cities', 'city')
            ->values([
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Paris',
                'population' => 27022,
                'country' => 'US'
            ])
            ->values([
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ])
            ->refresh()
            ->execute()
        ;

        $response = $this->query->from('test_cities', 'city')
            ->should(function (ElasticsearchQuery $query) {
                $query
                    ->whereRaw(
                        (new QueryString('par%'))
                            ->and()
                            ->defaultField('name')
                            ->analyzeWildcard()
                            ->useLikeSyntax()
                    )
                    ->orWhereRaw(new MatchPhrase('name', 'par'))
                ;
            })
            ->filter(new MatchBoolean('country', 'FR'))
            ->map(function ($doc) { return $doc['_source']; })
            ->order('population', 'desc')
            ->paginate()
        ;

        $this->assertInstanceOf(ElasticsearchPaginator::class, $response);
        $this->assertCount(2, $response);
        $this->assertEquals([
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR'
        ], $response->get(0));
        $this->assertEquals([
            'name' => 'Parthenay',
            'population' => 11599,
            'country' => 'FR'
        ], $response->get(1));
    }

    public function test_update_simple()
    {
        $create = new ElasticsearchCreateQuery(self::getClient());
        $create
            ->into('test_cities', 'city')
            ->values([
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Paris',
                'population' => 27022,
                'country' => 'US'
            ])
            ->values([
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ])
            ->refresh()
            ->execute()
        ;

        $result = $this->query->from('test_cities')
            ->where('population', '>', 20000)
            ->update('ctx._source.population += 10000')
        ;

        $this->assertTrue($result->hasWrite());
        $this->assertTrue($result->isWrite());
        $this->assertFalse($result->isRead());
        $this->assertEquals(3, $result->total());
        $this->assertEquals(3, $result->updated());
        $this->assertEquals(3, count($result));
        $this->assertEquals(0, $result->deleted());
        $this->assertEquals(0, $result->noops());

        self::getClient()->refreshIndex('test_cities');

        $this->assertEquals([
            [
                'name' => 'Paris',
                'population' => 2211578,
                'country' => 'FR'
            ],
            [
                'name' => 'Paris',
                'population' => 37022,
                'country' => 'US'
            ],
            [
                'name' => 'Cavaillon',
                'population' => 36689,
                'country' => 'FR'
            ],
        ], $this->query->map(fn ($doc) => $doc['_source'])->all());
    }

    public function test_update_noop()
    {
        $create = new ElasticsearchCreateQuery(self::getClient());
        $create
            ->into('test_cities', 'city')
            ->values([
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Paris',
                'population' => 27022,
                'country' => 'US'
            ])
            ->values([
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ])
            ->refresh()
            ->execute()
        ;

        $result = $this->query->from('test_cities')
            ->where('population', '>', 20000)
            ->update('ctx.op = "noop"')
        ;

        $this->assertFalse($result->hasWrite());
        $this->assertTrue($result->isWrite());
        $this->assertFalse($result->isRead());
        $this->assertEquals(3, $result->total());
        $this->assertEquals(0, $result->updated());
        $this->assertEquals(0, count($result));
        $this->assertEquals(0, $result->deleted());
        $this->assertEquals(3, $result->noops());
    }

    public function test_update_delete()
    {
        $create = new ElasticsearchCreateQuery(self::getClient());
        $create
            ->into('test_cities', 'city')
            ->values([
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Paris',
                'population' => 27022,
                'country' => 'US'
            ])
            ->values([
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ])
            ->refresh()
            ->execute()
        ;

        $result = $this->query->from('test_cities')
            ->where('population', '>', 20000)
            ->update('ctx.op = "delete"')
        ;

        $this->assertTrue($result->hasWrite());
        $this->assertTrue($result->isWrite());
        $this->assertFalse($result->isRead());
        $this->assertEquals(3, $result->total());
        $this->assertEquals(0, $result->updated());
        $this->assertEquals(3, count($result));
        $this->assertEquals(3, $result->deleted());
        $this->assertEquals(0, $result->noops());

        self::getClient()->refreshIndex('test_cities');

        $this->assertEmpty($this->query->all());
    }

    public function test_delete()
    {
        $create = new ElasticsearchCreateQuery(self::getClient());
        $create
            ->into('test_cities', 'city')
            ->values([
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Paris',
                'population' => 27022,
                'country' => 'US'
            ])
            ->values([
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR'
            ])
            ->values([
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ])
            ->refresh()
            ->execute()
        ;

        $result = $this->query->from('test_cities')
            ->where('population', '>', 20000)
            ->delete()
        ;

        $this->assertTrue($result->hasWrite());
        $this->assertTrue($result->isWrite());
        $this->assertFalse($result->isRead());
        $this->assertEquals(3, $result->total());
        $this->assertEquals(0, $result->updated());
        $this->assertEquals(3, count($result));
        $this->assertEquals(3, $result->deleted());
        $this->assertEquals(0, $result->noops());

        self::getClient()->refreshIndex('test_cities');

        $this->assertEmpty($this->query->all());
    }
}
