<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query;

use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\Match;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Conflict409Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class ElasticsearchCreateQueryTest
 */
class ElasticsearchCreateQueryTest extends TestCase
{
    /**
     * @var ElasticsearchCreateQuery
     */
    private $query;

    /**
     * @var Client
     */
    private $client;

    protected function setUp(): void
    {
        $this->query = new ElasticsearchCreateQuery(
            $this->client = ClientBuilder::fromConfig([
                'hosts' => ['127.0.0.1:9200']
            ])
        );
    }

    protected function tearDown(): void
    {
        if ($this->client->indices()->exists(['index' => ['test_persons']])) {
            $this->client->indices()->delete(['index' => ['test_persons']]);
        }
    }

    /**
     *
     */
    public function test_insert_bulk()
    {
        $response = $this->query
            ->into('test_persons', 'person')
            ->values([
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ])
            ->values([
                'firstName' => 'Minnie',
                'lastName' => 'Mouse'
            ])
            ->refresh()
            ->execute()
        ;

        $this->assertCount(2, $response['items']);

        $this->assertEquals(2, $this->search()->execute()['hits']['total']);
        $this->assertEquals([
            'firstName' => 'Mickey',
            'lastName' => 'Mouse'
        ], $this->search()->where(new Match('firstName', 'Mickey'))->execute()['hits']['hits'][0]['_source']);
        $this->assertEquals([
            'firstName' => 'Minnie',
            'lastName' => 'Mouse'
        ], $this->search()->where(new Match('firstName', 'Minnie'))->execute()['hits']['hits'][0]['_source']);
    }

    /**
     *
     */
    public function test_insert_bulk_twice_with_same_id()
    {
        $this->query
            ->into('test_persons', 'person')
            ->values([
                '_id' => 1,
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ])
            ->refresh()
            ->execute()
        ;

        $response = $this->query
            ->into('test_persons', 'person')
            ->values([
                '_id' => 1,
                'firstName' => 'Minnie',
                'lastName' => 'Mouse'
            ])
            ->execute()
        ;

        $this->assertTrue($response['errors']);
        $this->assertEquals('document_already_exists_exception', $response['items'][0]['create']['error']['type']);

        $this->assertEquals(1, $this->search()->execute()['hits']['total']);

        $this->assertEquals([
            'firstName' => 'Mickey',
            'lastName' => 'Mouse'
        ], $this->search()->execute()['hits']['hits'][0]['_source']);
    }

    /**
     *
     */
    public function test_replace_bulk_twice_with_same_id()
    {
        $this->query
            ->into('test_persons', 'person')
            ->values([
                '_id' => 1,
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ])
            ->refresh()
            ->execute()
        ;

        $response = $this->query
            ->into('test_persons', 'person')
            ->replace()
            ->values([
                '_id' => 1,
                'firstName' => 'Minnie',
                'lastName' => 'Mouse'
            ])
            ->execute()
        ;

        $this->assertFalse($response['errors']);
        $this->assertEquals(1, $this->search()->execute()['hits']['total']);

        sleep(2);

        $this->assertEquals([
            'firstName' => 'Minnie',
            'lastName' => 'Mouse'
        ], $this->search()->execute()['hits']['hits'][0]['_source']);
    }

    /**
     *
     */
    public function test_insert_bulk_with_missing_columns()
    {
        $this->query
            ->into('test_persons', 'person')
            ->values([
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ])
            ->values([
                'firstName' => 'Minnie',
            ])
            ->refresh()
            ->execute()
        ;

        $this->assertEquals([
            'firstName' => 'Mickey',
            'lastName' => 'Mouse'
        ], $this->search()->where(new Match('firstName', 'Mickey'))->execute()['hits']['hits'][0]['_source']);
        $this->assertEquals([
            'firstName' => 'Minnie',
            'lastName' => null
        ], $this->search()->where(new Match('firstName', 'Minnie'))->execute()['hits']['hits'][0]['_source']);
    }

    /**
     *
     */
    public function test_insert_bulk_without_columns_filter()
    {
        $this->query
            ->into('test_persons', 'person')
            ->values([
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ])
            ->values([
                'firstName' => 'Minnie',
            ])
            ->columns([])
            ->refresh()
            ->execute()
        ;

        $this->assertEquals([
            'firstName' => 'Mickey',
            'lastName' => 'Mouse'
        ], $this->search()->where(new Match('firstName', 'Mickey'))->execute()['hits']['hits'][0]['_source']);
        $this->assertEquals([
            'firstName' => 'Minnie',
        ], $this->search()->where(new Match('firstName', 'Minnie'))->execute()['hits']['hits'][0]['_source']);
    }

    /**
     *
     */
    public function test_insert()
    {
        $response = $this->query
            ->into('test_persons', 'person')
            ->bulk(false)
            ->values([
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ])
            ->refresh()
            ->execute()
        ;

        $this->assertTrue($response['created']);

        $this->assertEquals(1, $this->search()->execute()['hits']['total']);
        $this->assertEquals([
            'firstName' => 'Mickey',
            'lastName' => 'Mouse'
        ], $this->search()->where(new Match('firstName', 'Mickey'))->execute()['hits']['hits'][0]['_source']);
    }

    /**
     *
     */
    public function test_insert_same_id_twice()
    {
        $this->expectException(Conflict409Exception::class);

        $this->query
            ->into('test_persons', 'person')
            ->bulk(false)
            ->values([
                '_id' => 1,
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ])
            ->execute()
        ;

        $this->query
            ->into('test_persons', 'person')
            ->bulk(false)
            ->values([
                '_id' => 1,
                'firstName' => 'Minnie',
                'lastName' => 'Mouse'
            ])
            ->execute()
        ;
    }

    /**
     *
     */
    public function test_insert_replace_same_id_twice()
    {
        $this->query
            ->into('test_persons', 'person')
            ->bulk(false)
            ->values([
                '_id' => 1,
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ])
            ->execute()
        ;

        $response = $this->query
            ->into('test_persons', 'person')
            ->bulk(false)
            ->replace()
            ->values([
                '_id' => 1,
                'firstName' => 'Minnie',
                'lastName' => 'Mouse'
            ])
            ->refresh()
            ->execute()
        ;

        $this->assertFalse($response['created']);

        $this->assertEquals(1, $this->search()->execute()['hits']['total']);
        $this->assertEquals([
            'firstName' => 'Minnie',
            'lastName' => 'Mouse'
        ], $this->search()->where(new Match('firstName', 'Minnie'))->execute()['hits']['hits'][0]['_source']);
    }

    /**
     *
     */
    public function test_compile_simple_empty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No value to create');

        $this->query->into('test_persons', 'person')->bulk(false)->compile();
    }

    /**
     *
     */
    public function test_compile_simple()
    {
        $compiled = $this->query
            ->into('test_persons', 'person')
            ->bulk(false)
            ->values([
                '_id' => 1,
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ])
            ->compile()
        ;

        $this->assertEquals([
            'index' => 'test_persons',
            'type'  => 'person',
            'id'    => 1,
            'body'  => [
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ]
        ], $compiled);
    }

    /**
     *
     */
    public function test_compile_bulk()
    {
        $compiled = $this->query
            ->into('test_persons', 'person')
            ->values([
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ])
            ->values([
                'firstName' => 'Minnie',
                'lastName' => 'Mouse'
            ])
            ->compile()
        ;

        $this->assertEquals([
            'body' => [
                ['create' => ['_index' => 'test_persons', '_type' => 'person']],
                [
                    'firstName' => 'Mickey',
                    'lastName' => 'Mouse'
                ],
                ['create' => ['_index' => 'test_persons', '_type' => 'person']],
                [
                    'firstName' => 'Minnie',
                    'lastName' => 'Mouse'
                ],
            ]
        ], $compiled);
    }

    /**
     *
     */
    public function test_count_clear()
    {
        $query = $this->query->into('test_persons', 'person');

        $this->assertCount(0, $query);

        $query
            ->values([
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ])
            ->values([
                'firstName' => 'Minnie',
                'lastName' => 'Mouse'
            ])
        ;

        $this->assertCount(2, $query);
        $this->assertCount(0, $query->clear());
    }

    /**
     *
     */
    public function test_refresh_simple()
    {
        $compiled = $this->query
            ->into('test_persons', 'person')
            ->bulk(false)
            ->values([
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ])
            ->refresh()
            ->compile()
        ;

        $this->assertEquals([
            'index' => 'test_persons',
            'type'  => 'person',
            'refresh' => true,
            'body'  => [
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ]
        ], $compiled);
    }

    /**
     *
     */
    public function test_refresh_bulk()
    {
        $compiled = $this->query
            ->into('test_persons', 'person')
            ->values([
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ])
            ->refresh()
            ->compile()
        ;

        $this->assertEquals([
            'refresh' => true,
            'body' => [
                ['create' => ['_index' => 'test_persons', '_type' => 'person']],
                [
                    'firstName' => 'Mickey',
                    'lastName' => 'Mouse'
                ]
            ]
        ], $compiled);
    }

    public function search(): ElasticsearchQuery
    {
        return (new ElasticsearchQuery($this->client))->from('test_persons', 'person');
    }
}
