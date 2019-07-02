<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query;

use Bdf\Collection\Stream\ArrayStream;
use Bdf\Prime\Indexer\Elasticsearch\Query\Compound\FunctionScoreQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\Match;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\MatchPhrase;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\QueryString;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\Range;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\Wildcard;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Class ElasticsearchQueryTest
 */
class ElasticsearchQueryTest extends TestCase
{
    /**
     * @var ElasticsearchQuery
     */
    private $query;

    /**
     * @var Client
     */
    private $client;

    protected function setUp()
    {
        $this->query = new ElasticsearchQuery(
            $this->client = ClientBuilder::fromConfig([
                'hosts' => ['127.0.0.1:9222']
            ])
        );
    }

    protected function tearDown()
    {
        if ($this->client->indices()->exists(['index' => 'test_cities'])) {
            $this->client->indices()->delete(['index' => 'test_cities']);
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
    public function test_with_with_custom_filters()
    {
        $this->query = new ElasticsearchQuery(
            $this->client,
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
            $this->client,
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
            $this->client,
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
            ->filter(new Match('country', 'FR'))
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
    public function test_execute_functional()
    {
        $create = new ElasticsearchCreateQuery($this->client);
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
            ->execute()
        ;

        sleep(2);

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
            ->filter(new Match('country', 'FR'))
            ->execute()['hits']
        ;

        $this->assertEquals(2, $response['total']);
        $this->assertEquals([
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR'
        ], $response['hits'][0]['_source']);
        $this->assertEquals([
            'name' => 'Parthenay',
            'population' => 11599,
            'country' => 'FR'
        ], $response['hits'][1]['_source']);
    }

    /**
     *
     */
    public function test_stream_functional()
    {
        $create = new ElasticsearchCreateQuery($this->client);
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

        $stream = $this->query->from('test_cities', 'city')->stream();

        $this->assertInstanceOf(ArrayStream::class, $stream);

        $this->assertCount(2, $stream);
        $this->assertEquals([
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
        ], $stream->map(function (array $data) { return $data['_source']; })->toArray());
    }
}
