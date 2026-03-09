<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Compound;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammar;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\MatchBoolean;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\Missing;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\Range;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\WhereFilter;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\Wildcard;
use PHPUnit\Framework\TestCase;

/**
 * Class BooleanQueryTest
 */
class BooleanQueryTest extends TestCase
{
    /**
     *
     */
    public function test_simple()
    {
        $bool = new BooleanQuery();

        $bool
            ->must((new Range('population'))->gt(10000))
            ->should(new MatchBoolean('name', 'Paris'))
            ->should(new MatchBoolean('zipCode', '75000'))
            ->filter(new MatchBoolean('country', 'FR'))
            ->mustNot(new Missing('population'))
        ;

        $this->assertEquals([
            'bool' => [
                'minimum_should_match' => 1,
                'must' => [['range' => ['population' => ['gt' => 10000]]]],
                'should' => [
                    ['match' => ['name' => 'Paris']],
                    ['match' => ['zipCode' => '75000']],
                ],
                'filter' => [['match' => ['country' => 'FR']]],
                'must_not' => [['missing' => ['field' => 'population']]],
            ]
        ], $bool->compile(new ElasticsearchGrammar()));
    }

    public function test_removeFilter_not_matching()
    {
        $bool = new BooleanQuery();
        $bool
            ->filter(new WhereFilter('name', '=', 'Paris'))
            ->filter(new WhereFilter('zipCode', '=', '75000'))
        ;

        $removed = clone $bool;
        $this->assertFalse($removed->removeFilter(fn ($filter) => $filter->column() === 'not_found'));
        $this->assertEquals($bool, $removed);
    }

    public function test_removeFilter_matching()
    {
        $bool = new BooleanQuery();
        $bool
            ->filter(new WhereFilter('name', '=', 'Paris'))
            ->filter(new WhereFilter('zipCode', '=', '75000'))
        ;

        $this->assertTrue($bool->removeFilter(fn ($filter) => $filter->column() === 'name'));
        $this->assertEquals((new BooleanQuery())->filter(new WhereFilter('zipCode', '=', '75000')), $bool);
    }

    public function test_removeFilter_matching_multiple()
    {
        $bool = new BooleanQuery();
        $bool
            ->filter(new WhereFilter('name', '=', 'Paris'))
            ->filter(new WhereFilter('zipCode', '=', '75000'))
            ->filter(new WhereFilter('name', '>', 'A'))
            ->filter(new WhereFilter('name', '<', 'Z'))
        ;

        $this->assertTrue($bool->removeFilter(fn ($filter) => $filter->column() === 'name'));
        $this->assertEquals((new BooleanQuery())->filter(new WhereFilter('zipCode', '=', '75000')), $bool);
    }

    /**
     *
     */
    public function test_minimumShouldMatch()
    {
        $bool = new BooleanQuery();

        $bool
            ->should(new MatchBoolean('name', 'Paris'))
            ->should(new MatchBoolean('zipCode', '75000'))
            ->minimumShouldMatch(2)
        ;

        $this->assertEquals([
            'bool' => [
                'should' => [
                    ['match' => ['name' => 'Paris']],
                    ['match' => ['zipCode' => '75000']],
                ],
                'minimum_should_match' => 2
            ]
        ], $bool->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_boost()
    {
        $bool = new BooleanQuery();

        $bool
            ->should(new MatchBoolean('name', 'Paris'))
            ->should(new MatchBoolean('zipCode', '75000'))
            ->boost(2)
        ;

        $this->assertEquals([
            'bool' => [
                'minimum_should_match' => 1,
                'should' => [
                    ['match' => ['name' => 'Paris']],
                    ['match' => ['zipCode' => '75000']],
                ],
                'boost' => 2
            ]
        ], $bool->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_or()
    {
        $bool = new BooleanQuery();

        $bool
            ->filter(new MatchBoolean('name', 'Paris'))
            ->filter(new MatchBoolean('zipCode', '75000'))
            ->or()
            ->filter(new Wildcard('name', 'P*'))
            ->filter(new Wildcard('zipCode', '75*'))
        ;

        $this->assertEquals([
            'bool' => [
                'minimum_should_match' => 1,
                'should' => [
                    ['bool' => [
                        'filter' => [
                            ['match' => ['name' => 'Paris']],
                            ['match' => ['zipCode' => '75000']],
                        ]
                    ]],
                    ['bool' => [
                        'filter' => [
                            ['prefix' => ['name' => 'P']],
                            ['prefix' => ['zipCode' => '75']],
                        ]
                    ]],
                ],
            ]
        ], $bool->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_and()
    {
        $bool = new BooleanQuery();

        $bool
            ->should(new MatchBoolean('name', 'Paris'))
            ->and()
            ->filter(new MatchBoolean('zipCode', '75000'))
        ;

        $bool
            ->should(new Wildcard('name', 'P*'))
            ->and()
            ->filter(new Wildcard('zipCode', '75*'))
            ->and()
            ->filter((new Range('population'))->gt(10000))
        ;

        $this->assertEquals([
            'bool' => [
                'minimum_should_match' => 1,
                'should' => [
                    ['bool' => [
                        'filter' => [
                            ['match' => ['name' => 'Paris']],
                            ['match' => ['zipCode' => '75000']],
                        ]
                    ]],
                    ['bool' => [
                        'filter' => [
                            ['prefix' => ['name' => 'P']],
                            ['prefix' => ['zipCode' => '75']],
                            ['range' => ['population' => ['gt' => 10000]]],
                        ]
                    ]],
                ],
            ]
        ], $bool->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_optimize_not()
    {
        $bool = new BooleanQuery();

        $bool
            ->filter((new BooleanQuery())->mustNot(new MatchBoolean('name', 'John')))
            ->filter(new Wildcard('name', 'J*'))
            ->filter((new BooleanQuery())->mustNot(new Missing('age')))
        ;

        $this->assertEquals([
            'bool' => [
                'filter' => [
                    ['prefix' => ['name' => 'J']]
                ],
                'must_not' => [
                    ['match' => ['name' => 'John']],
                    ['missing' => ['field' => 'age']],
                ]
            ]
        ], $bool->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_single_nested_boolean_query()
    {
        $bool = new BooleanQuery();

        $bool->filter((new BooleanQuery())->filter(new MatchBoolean('name', 'John')));

        $this->assertEquals([
            'bool' => [
                'filter' => [
                    ['match' => ['name' => 'John']]
                ],
            ]
        ], $bool->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_optimize_single_nested_filter()
    {
        $bool = new BooleanQuery();

        $bool
            ->filter((new BooleanQuery())->filter(new MatchBoolean('name', 'John')))
            ->must((new BooleanQuery())->must(new MatchBoolean('bar', 'foo')))
        ;

        $this->assertEquals([
            'bool' => [
                'filter' => [
                    ['match' => ['name' => 'John']]
                ],
                'must' => [
                    ['match' => ['bar' => 'foo']]
                ],
            ]
        ], $bool->compile(new ElasticsearchGrammar()));
    }

    /**
     *
     */
    public function test_optimize_single_nested_filter_with_option()
    {
        $bool = new BooleanQuery();

        $bool
            ->minimumShouldMatch(1)
            ->should((new BooleanQuery())->should(new MatchBoolean('foo', 'bar'))->minimumShouldMatch(1))
        ;

        $this->assertEquals([
            'bool' => [
                'minimum_should_match' => 1,
                'should' => [
                    ['match' => ['foo' => 'bar']]
                ],
            ]
        ], $bool->compile(new ElasticsearchGrammar()));
    }
}
