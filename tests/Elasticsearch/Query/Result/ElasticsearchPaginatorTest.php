<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Result;

use Bdf\Prime\Indexer\Elasticsearch\Query\ElasticsearchCreateQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\ElasticsearchQuery;
use Bdf\Prime\Indexer\IndexTestCase;
use Elastic\Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use PHPUnit\Framework\TestCase;

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
}
