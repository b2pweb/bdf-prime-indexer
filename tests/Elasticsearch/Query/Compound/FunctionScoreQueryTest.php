<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Compound;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammar;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\MatchBoolean;
use PHPUnit\Framework\TestCase;

/**
 * Class FunctionScoreQueryTest
 */
class FunctionScoreQueryTest extends TestCase
{
    /**
     *
     */
    public function test_single_function()
    {
        $query = new FunctionScoreQuery();

        $query
            ->addFunction('field_value_factor', [
                'field' => 'population',
                'factor' => 1,
                'modifier' => 'log1p'
            ])
            ->wrap(new MatchBoolean('name', 'Paris'))
        ;

        $this->assertEquals([
            'function_score' => [
                'field_value_factor' => [
                    'field' => 'population',
                    'factor' => 1,
                    'modifier' => 'log1p'
                ],
                'query' => ['match' => ['name' => 'Paris']]
            ]
        ], $query->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_multiple_functions()
    {
        $query = new FunctionScoreQuery();

        $query
            ->addFunction('field_value_factor', [
                'field' => 'population',
                'factor' => 1,
                'modifier' => 'log1p'
            ],  [], 1.3)
            ->addFunction('linear', [
                'position' => [
                    'origin' => '5.15,45.23',
                    'scale' => '10km',
                    'decay' => .2
                ]
            ])
            ->wrap(new MatchBoolean('name', 'Paris'))
        ;

        $this->assertEquals([
            'function_score' => [
                'functions' => [
                    [
                        'field_value_factor' => [
                            'field' => 'population',
                            'factor' => 1,
                            'modifier' => 'log1p'
                        ],
                        'weight' => 1.3
                    ],
                    [
                        'linear' => [
                            'position' => [
                                'origin' => '5.15,45.23',
                                'scale' => '10km',
                                'decay' => .2
                            ]
                        ]
                    ]
                ],
                'query' => ['match' => ['name' => 'Paris']]
            ]
        ], $query->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_maxBoost()
    {
        $query = new FunctionScoreQuery();

        $query
            ->addFunction('field_value_factor', [
                'field' => 'population',
                'factor' => 1,
                'modifier' => 'log1p'
            ])
            ->maxBoost(5)
            ->wrap(new MatchBoolean('name', 'Paris'))
        ;

        $this->assertEquals([
            'function_score' => [
                'field_value_factor' => [
                    'field' => 'population',
                    'factor' => 1,
                    'modifier' => 'log1p'
                ],
                'max_boost' => 5,
                'query' => ['match' => ['name' => 'Paris']]
            ]
        ], $query->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_boostMode()
    {
        $query = new FunctionScoreQuery();

        $query
            ->addFunction('field_value_factor', [
                'field' => 'population',
                'factor' => 1,
                'modifier' => 'log1p'
            ])
            ->boostMode('sum')
            ->wrap(new MatchBoolean('name', 'Paris'))
        ;

        $this->assertEquals([
            'function_score' => [
                'field_value_factor' => [
                    'field' => 'population',
                    'factor' => 1,
                    'modifier' => 'log1p'
                ],
                'boost_mode' => 'sum',
                'query' => ['match' => ['name' => 'Paris']]
            ]
        ], $query->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_scoreMode()
    {
        $query = new FunctionScoreQuery();

        $query
            ->addFunction('field_value_factor', [
                'field' => 'population',
                'factor' => 1,
                'modifier' => 'log1p'
            ])
            ->scoreMode('sum')
            ->wrap(new MatchBoolean('name', 'Paris'))
        ;

        $this->assertEquals([
            'function_score' => [
                'field_value_factor' => [
                    'field' => 'population',
                    'factor' => 1,
                    'modifier' => 'log1p'
                ],
                'score_mode' => 'sum',
                'query' => ['match' => ['name' => 'Paris']]
            ]
        ], $query->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_minScore()
    {
        $query = new FunctionScoreQuery();

        $query
            ->addFunction('field_value_factor', [
                'field' => 'population',
                'factor' => 1,
                'modifier' => 'log1p'
            ])
            ->minScore(1.6)
            ->wrap(new MatchBoolean('name', 'Paris'))
        ;

        $this->assertEquals([
            'function_score' => [
                'field_value_factor' => [
                    'field' => 'population',
                    'factor' => 1,
                    'modifier' => 'log1p'
                ],
                'min_score' => 1.6,
                'query' => ['match' => ['name' => 'Paris']]
            ]
        ], $query->compile(new ElasticsearchGrammar()));
    }
}
