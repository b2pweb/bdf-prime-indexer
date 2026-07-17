<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Result;

use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\ElasticsearchCreateQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\ElasticsearchQuery;
use Bdf\Prime\Indexer\IndexTestCase;
use Bdf\Prime\Query\Pagination\PaginatorInterface;

/**
 * Class ElasticsearchPaginatorTest
 */
class ElasticsearchPaginatorTest extends IndexTestCase
{
    /**
     * @var ElasticsearchQuery
     */
    private $query;

    protected function setUp(): void
    {
        $this->query = new ElasticsearchQuery(self::getClient());

        $this->query->from('test_cities', 'city')->order('population', 'desc');

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
    public function test_default()
    {
        $paginator = new ElasticsearchPaginator($this->query, null, null, function ($doc) { return $doc['_source']; });

        $this->assertCount(4, $paginator);
        $this->assertEquals(4, $paginator->size());
        $this->assertEquals(20, $paginator->limit());
        $this->assertEquals(20, $paginator->pageMaxRows());
        $this->assertEquals(1, $paginator->page());
        $this->assertEquals(0, $paginator->offset());
        $this->assertEquals([
            [
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ],
            [
                'name' => 'Paris',
                'population' => 27022,
                'country' => 'US'
            ],
            [
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ],
            [
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR'
            ],
        ], iterator_to_array($paginator));
    }

    /**
     *
     */
    public function test_maxRows()
    {
        $paginator = new ElasticsearchPaginator($this->query, 2, null, function ($doc) { return $doc['_source']; });

        $this->assertCount(2, $paginator);
        $this->assertEquals(4, $paginator->size());
        $this->assertEquals(2, $paginator->limit());
        $this->assertEquals(2, $paginator->pageMaxRows());
        $this->assertEquals(1, $paginator->page());
        $this->assertEquals(0, $paginator->offset());
        $this->assertEquals([
            [
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR'
            ],
            [
                'name' => 'Paris',
                'population' => 27022,
                'country' => 'US'
            ],
        ], iterator_to_array($paginator));
    }

    /**
     *
     */
    public function test_with_page()
    {
        $paginator = new ElasticsearchPaginator($this->query, 2, 2, function ($doc) { return $doc['_source']; });

        $this->assertCount(2, $paginator);
        $this->assertEquals(4, $paginator->size());
        $this->assertEquals(2, $paginator->limit());
        $this->assertEquals(2, $paginator->pageMaxRows());
        $this->assertEquals(2, $paginator->page());
        $this->assertEquals(2, $paginator->offset());
        $this->assertEquals([
            [
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR'
            ],
            [
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR'
            ],
        ], iterator_to_array($paginator));
    }

    /**
     *
     */
    public function test_page_too_high()
    {
        $paginator = new ElasticsearchPaginator($this->query, 2, 10);

        $this->assertCount(0, $paginator);
        $this->assertEquals(4, $paginator->size());
        $this->assertEquals(10, $paginator->page());
        $this->assertEquals([], iterator_to_array($paginator));
    }

    /**
     *
     */
    public function test_without_transformer()
    {
        $paginator = new ElasticsearchPaginator($this->query);

        $this->assertCount(4, $paginator);
        $this->assertEquals([
            ['name' => 'Paris', 'population' => 2201578, 'country' => 'FR'],
            ['name' => 'Paris', 'population' => 27022, 'country' => 'US'],
            ['name' => 'Cavaillon', 'population' => 26689, 'country' => 'FR'],
            ['name' => 'Parthenay', 'population' => 11599, 'country' => 'FR'],
        ], array_column($paginator->all(), '_source'));
    }

    /**
     *
     */
    public function test_getIterator_should_return_the_collection()
    {
        $paginator = $this->paginator();

        $this->assertInstanceOf(CollectionInterface::class, $paginator->getIterator());
        $this->assertSame($paginator->collection(), $paginator->getIterator());
    }

    /**
     *
     */
    public function test_query()
    {
        $paginator = $this->paginator();

        $this->assertSame($this->query, $paginator->query());
    }

    /**
     *
     */
    public function test_collection()
    {
        $paginator = $this->paginator();

        $this->assertInstanceOf(CollectionInterface::class, $paginator->collection());
        $this->assertEquals([
            ['name' => 'Paris', 'population' => 2201578, 'country' => 'FR'],
            ['name' => 'Paris', 'population' => 27022, 'country' => 'US'],
            ['name' => 'Cavaillon', 'population' => 26689, 'country' => 'FR'],
            ['name' => 'Parthenay', 'population' => 11599, 'country' => 'FR'],
        ], $paginator->collection()->all());
    }

    /**
     *
     */
    public function test_order()
    {
        $paginator = $this->paginator();

        $this->assertEquals(['population' => 'desc'], $paginator->order());
        $this->assertEquals('desc', $paginator->order('population'));
        $this->assertNull($paginator->order('name'));
    }

    /**
     *
     */
    public function test_order_when_empty()
    {
        $query = new ElasticsearchQuery(self::getClient());
        $query->from('test_cities', 'city');

        $paginator = new ElasticsearchPaginator($query);

        $this->assertEquals([], $paginator->order());
        $this->assertNull($paginator->order('population'));
    }

    /**
     *
     */
    public function test_push()
    {
        $paginator = $this->paginator();
        $city = ['name' => 'Marseille', 'population' => 861635, 'country' => 'FR'];

        $this->assertSame($paginator, $paginator->push($city));
        $this->assertCount(5, $paginator);
        $this->assertEquals($city, $paginator->get(4));
    }

    /**
     *
     */
    public function test_pushAll_should_replace_items()
    {
        $paginator = $this->paginator();
        $cities = [
            ['name' => 'Marseille', 'population' => 861635, 'country' => 'FR'],
            ['name' => 'Lyon', 'population' => 513275, 'country' => 'FR'],
        ];

        $this->assertSame($paginator, $paginator->pushAll($cities));
        $this->assertCount(2, $paginator);
        $this->assertEquals($cities, $paginator->all());
    }

    /**
     *
     */
    public function test_put()
    {
        $paginator = $this->paginator();
        $city = ['name' => 'Marseille', 'population' => 861635, 'country' => 'FR'];

        $this->assertSame($paginator, $paginator->put('marseille', $city));
        $this->assertEquals($city, $paginator->get('marseille'));
    }

    /**
     *
     */
    public function test_get()
    {
        $paginator = $this->paginator();

        $this->assertEquals(['name' => 'Paris', 'population' => 2201578, 'country' => 'FR'], $paginator->get(0));
        $this->assertNull($paginator->get('not_found'));
        $this->assertEquals('default', $paginator->get('not_found', 'default'));
    }

    /**
     *
     */
    public function test_has()
    {
        $paginator = $this->paginator();

        $this->assertTrue($paginator->has(0));
        $this->assertFalse($paginator->has('not_found'));
    }

    /**
     *
     */
    public function test_remove()
    {
        $paginator = $this->paginator();

        $this->assertSame($paginator, $paginator->remove(0));
        $this->assertCount(3, $paginator);
        $this->assertFalse($paginator->has(0));
    }

    /**
     *
     */
    public function test_array_access()
    {
        $paginator = $this->paginator();

        $this->assertTrue(isset($paginator[0]));
        $this->assertFalse(isset($paginator['not_found']));
        $this->assertEquals(['name' => 'Paris', 'population' => 2201578, 'country' => 'FR'], $paginator[0]);

        $city = ['name' => 'Marseille', 'population' => 861635, 'country' => 'FR'];
        $paginator['marseille'] = $city;
        $this->assertEquals($city, $paginator['marseille']);

        $paginator[] = $city;
        $this->assertEquals($city, $paginator[4]);

        unset($paginator[0]);
        $this->assertFalse(isset($paginator[0]));
        $this->assertCount(5, $paginator);
    }

    /**
     *
     */
    public function test_all()
    {
        $paginator = $this->paginator();

        $this->assertEquals([
            ['name' => 'Paris', 'population' => 2201578, 'country' => 'FR'],
            ['name' => 'Paris', 'population' => 27022, 'country' => 'US'],
            ['name' => 'Cavaillon', 'population' => 26689, 'country' => 'FR'],
            ['name' => 'Parthenay', 'population' => 11599, 'country' => 'FR'],
        ], $paginator->all());
    }

    /**
     *
     */
    public function test_clear_and_isEmpty()
    {
        $paginator = $this->paginator();

        $this->assertFalse($paginator->isEmpty());
        $this->assertSame($paginator, $paginator->clear());
        $this->assertTrue($paginator->isEmpty());
        $this->assertCount(0, $paginator);
    }

    /**
     *
     */
    public function test_keys()
    {
        $paginator = $this->paginator();

        $this->assertEquals([0, 1, 2, 3], $paginator->keys());
    }

    /**
     *
     */
    public function test_map()
    {
        $paginator = $this->paginator();

        $this->assertSame($paginator, $paginator->map(function ($city) { return $city['name']; }));
        $this->assertEquals(['Paris', 'Paris', 'Cavaillon', 'Parthenay'], $paginator->all());
    }

    /**
     *
     */
    public function test_filter()
    {
        $paginator = $this->paginator();

        $this->assertSame($paginator, $paginator->filter(function ($city) { return $city['country'] === 'FR'; }));
        $this->assertEquals([
            0 => ['name' => 'Paris', 'population' => 2201578, 'country' => 'FR'],
            2 => ['name' => 'Cavaillon', 'population' => 26689, 'country' => 'FR'],
            3 => ['name' => 'Parthenay', 'population' => 11599, 'country' => 'FR'],
        ], $paginator->all());
    }

    /**
     *
     */
    public function test_groupBy()
    {
        $paginator = $this->paginator();

        $this->assertSame($paginator, $paginator->groupBy(function ($city) { return $city['country']; }, PaginatorInterface::GROUPBY_COMBINE));
        $this->assertEquals([
            'FR' => [
                ['name' => 'Paris', 'population' => 2201578, 'country' => 'FR'],
                ['name' => 'Cavaillon', 'population' => 26689, 'country' => 'FR'],
                ['name' => 'Parthenay', 'population' => 11599, 'country' => 'FR'],
            ],
            'US' => [
                ['name' => 'Paris', 'population' => 27022, 'country' => 'US'],
            ],
        ], $paginator->all());
    }

    /**
     *
     */
    public function test_contains()
    {
        $paginator = $this->paginator();

        $this->assertTrue($paginator->contains(['name' => 'Paris', 'population' => 2201578, 'country' => 'FR']));
        $this->assertFalse($paginator->contains(['name' => 'Marseille', 'population' => 861635, 'country' => 'FR']));
        $this->assertTrue($paginator->contains(function ($city) { return $city['name'] === 'Cavaillon'; }));
        $this->assertFalse($paginator->contains(function ($city) { return $city['name'] === 'Marseille'; }));
    }

    /**
     *
     */
    public function test_indexOf()
    {
        $paginator = $this->paginator();

        $this->assertSame(1, $paginator->indexOf(['name' => 'Paris', 'population' => 27022, 'country' => 'US']));
        $this->assertFalse($paginator->indexOf(['name' => 'Marseille', 'population' => 861635, 'country' => 'FR']));
        $this->assertSame(2, $paginator->indexOf(function ($city) { return $city['name'] === 'Cavaillon'; }));
    }

    /**
     *
     */
    public function test_merge()
    {
        $paginator = $this->paginator();
        $cities = [
            ['name' => 'Marseille', 'population' => 861635, 'country' => 'FR'],
            ['name' => 'Lyon', 'population' => 513275, 'country' => 'FR'],
        ];

        $this->assertSame($paginator, $paginator->merge($cities));
        $this->assertCount(6, $paginator);
        $this->assertEquals($cities[0], $paginator->get(4));
        $this->assertEquals($cities[1], $paginator->get(5));
    }

    /**
     *
     */
    public function test_sort()
    {
        $paginator = $this->paginator();

        $this->assertSame($paginator, $paginator->sort(function ($a, $b) { return $a['population'] <=> $b['population']; }));
        $this->assertEquals([
            3 => ['name' => 'Parthenay', 'population' => 11599, 'country' => 'FR'],
            2 => ['name' => 'Cavaillon', 'population' => 26689, 'country' => 'FR'],
            1 => ['name' => 'Paris', 'population' => 27022, 'country' => 'US'],
            0 => ['name' => 'Paris', 'population' => 2201578, 'country' => 'FR'],
        ], $paginator->all());
    }

    /**
     *
     */
    public function test_collection_method_do_not_change_the_paginator_collection()
    {
        $paginator = $this->paginator();

        $collection = $paginator->collection()->map(function ($city) { return $city['name']; });

        $this->assertEquals(['name' => 'Paris', 'population' => 2201578, 'country' => 'FR'], $paginator->get(0));
        $this->assertEquals('Paris', $collection->get(0));
    }

    /**
     * Create a paginator on the base query, with a transformer extracting the document source
     *
     * @return ElasticsearchPaginator
     */
    private function paginator(): ElasticsearchPaginator
    {
        return new ElasticsearchPaginator($this->query, null, null, function ($doc) { return $doc['_source']; });
    }
}
