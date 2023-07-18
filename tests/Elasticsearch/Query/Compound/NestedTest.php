<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Compound;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammar;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\MatchBoolean;
use PHPUnit\Framework\TestCase;

class NestedTest extends TestCase
{
    /**
     *
     */
    public function test_simple()
    {
        $query = new Nested(
            'address',
            (new BooleanQuery())
                ->filter(new MatchBoolean('address.city', 'Paris'))
                ->filter(new MatchBoolean('address.country', 'France'))
        );

        $this->assertEquals([
            'nested' => [
                'path' => 'address',
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['match' => ['address.city' => 'Paris']],
                            ['match' => ['address.country' => 'France']]
                        ]
                    ]
                ]
            ]
        ], $query->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_scoreMode()
    {
        $query = new Nested(
            'address',
            (new BooleanQuery())
                ->filter(new MatchBoolean('address.city', 'Paris'))
                ->filter(new MatchBoolean('address.country', 'France'))
        );

        $query->scoreMode(Nested::SCORE_MODE_MAX);

        $this->assertEquals([
            'nested' => [
                'path' => 'address',
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['match' => ['address.city' => 'Paris']],
                            ['match' => ['address.country' => 'France']]
                        ]
                    ]
                ],
                'score_mode' => 'max',
            ]
        ], $query->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_ignoredUnmapped()
    {
        $query = new Nested(
            'address',
            (new BooleanQuery())
                ->filter(new MatchBoolean('address.city', 'Paris'))
                ->filter(new MatchBoolean('address.country', 'France'))
        );

        $query->ignoreUnmapped();

        $this->assertEquals([
            'nested' => [
                'path' => 'address',
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['match' => ['address.city' => 'Paris']],
                            ['match' => ['address.country' => 'France']]
                        ]
                    ]
                ],
                'ignore_unmapped' => true,
            ]
        ], $query->compile(new ElasticsearchGrammar()));
    }
}
