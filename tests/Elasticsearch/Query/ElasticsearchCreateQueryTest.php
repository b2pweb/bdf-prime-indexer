<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query;

use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\InvalidRequestException;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\MatchBoolean;
use Bdf\Prime\Indexer\Exception\InvalidQueryException;
use Bdf\Prime\Indexer\Exception\QueryExecutionException;
use Bdf\Prime\Indexer\IndexTestCase;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Conflict409Exception;

/**
 * Class ElasticsearchCreateQueryTest
 */
class ElasticsearchCreateQueryTest extends IndexTestCase
{
    /**
     * @var ElasticsearchCreateQuery
     */
    private $query;

    protected function setUp(): void
    {
        $this->query = new ElasticsearchCreateQuery(self::getClient());
    }

    protected function tearDown(): void
    {
        if (self::getClient()->hasIndex('test_persons')) {
            self::getClient()->deleteIndex('test_persons');
        }
    }

    /**
     *
     */
    public function test_insert_bulk()
    {
        $response = $this->query
            ->into('test_persons')
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

        $this->assertEquals('test_persons', $response->all()[0]['index']['_index']);
        $this->assertEquals(1, $response->all()[0]['index']['_version']);
        $this->assertEquals('created', $response->all()[0]['index']['result']);
        $this->assertTrue($response->all()[0]['index']['forced_refresh']);
        $this->assertNotEmpty($response->all()[0]['index']['_id']);
        $this->assertEquals('test_persons', $response->all()[1]['index']['_index']);
        $this->assertEquals(1, $response->all()[1]['index']['_version']);
        $this->assertEquals('created', $response->all()[1]['index']['result']);
        $this->assertTrue($response->all()[1]['index']['forced_refresh']);
        $this->assertNotEmpty($response->all()[1]['index']['_id']);

        $this->assertEquals(2, $this->search()->execute()->total());
        $this->assertEquals([
            'firstName' => 'Mickey',
            'lastName' => 'Mouse'
        ], $this->search()->where(new MatchBoolean('firstName', 'Mickey'))->execute()['hits']['hits'][0]['_source']);
        $this->assertEquals([
            'firstName' => 'Minnie',
            'lastName' => 'Mouse'
        ], $this->search()->where(new MatchBoolean('firstName', 'Minnie'))->execute()['hits']['hits'][0]['_source']);
    }

    /**
     *
     */
    public function test_insert_bulk_result_typed()
    {
        $response = $this->query
            ->into('test_persons')
            ->values([
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ])
            ->refresh()
            ->execute()
        ;

        $result = $response->asObject()->all()[0];
        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertEquals('test_persons', $result->index['_index']);

        $result = $response->asAssociative()->all()[0];
        $this->assertIsArray($result);
        $this->assertEquals('test_persons', $result['index']['_index']);

        $result = $response->asList()->all()[0];
        $this->assertIsArray($result);
        $this->assertEquals('test_persons', $result[0]['_index']);

        $result = $response->asColumn()->all()[0];
        $this->assertIsArray($result);
        $this->assertEquals('test_persons', $result['_index']);

        $result = $response->asClass(MyCustomResultClass::class)->all()[0];
        $this->assertInstanceOf(MyCustomResultClass::class, $result);
        $this->assertEquals('test_persons', $result->index());
    }

    /**
     *
     */
    public function test_insert_bulk_twice_with_same_id()
    {
        $this->query
            ->into('test_persons')
            ->values([
                '_id' => 1,
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ])
            ->refresh()
            ->execute()
        ;

        $response = $this->query
            ->into('test_persons')
            ->values([
                '_id' => 1,
                'firstName' => 'Minnie',
                'lastName' => 'Mouse'
            ])
            ->execute()
        ;

        $this->assertTrue($response['errors']);
        $this->assertContains($response['items'][0]['create']['error']['type'], ['document_already_exists_exception', 'version_conflict_engine_exception']);

        $this->assertEquals(1, $this->search()->execute()->total());

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
            ->into('test_persons')
            ->values([
                '_id' => 1,
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ])
            ->refresh()
            ->execute()
        ;

        $response = $this->query
            ->into('test_persons')
            ->replace()
            ->values([
                '_id' => 1,
                'firstName' => 'Minnie',
                'lastName' => 'Mouse'
            ])
            ->execute()
        ;

        $this->assertFalse($response['errors']);
        $this->assertEquals(1, $this->search()->execute()->total());

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
            ->into('test_persons')
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
        ], $this->search()->where(new MatchBoolean('firstName', 'Mickey'))->execute()['hits']['hits'][0]['_source']);
        $this->assertEquals([
            'firstName' => 'Minnie',
            'lastName' => null
        ], $this->search()->where(new MatchBoolean('firstName', 'Minnie'))->execute()['hits']['hits'][0]['_source']);
    }

    /**
     *
     */
    public function test_insert_bulk_without_columns_filter()
    {
        $this->query
            ->into('test_persons')
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
        ], $this->search()->where(new MatchBoolean('firstName', 'Mickey'))->execute()['hits']['hits'][0]['_source']);
        $this->assertEquals([
            'firstName' => 'Minnie',
        ], $this->search()->where(new MatchBoolean('firstName', 'Minnie'))->execute()['hits']['hits'][0]['_source']);
    }

    /**
     *
     */
    public function test_insert()
    {
        $response = $this->query
            ->into('test_persons')
            ->bulk(false)
            ->values([
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ])
            ->refresh()
            ->execute()
        ;

        $this->assertTrue($response['created']);
        $this->assertSame('created', $response['result']);
        $this->assertTrue($response->creation());
        $this->assertFalse($response->update());
        $this->assertFalse($response->deletion());
        $this->assertTrue($response->isWrite());
        $this->assertTrue($response->hasWrite());
        $this->assertFalse($response->isRead());
        $this->assertCount(1, $response);

        $this->assertEmpty(iterator_to_array($response));
        $this->assertEmpty($response->all());
        $this->assertSame($response, $response->asAssociative()->asList()->asObject()->asClass(\stdClass::class)->asColumn(1)->fetchMode(ResultSetInterface::FETCH_ASSOC));
        $this->assertArrayHasKey('result', $response);
        $this->assertArrayNotHasKey('foo', $response);

        $this->assertEquals(1, $this->search()->execute()->total());
        $this->assertEquals([
            'firstName' => 'Mickey',
            'lastName' => 'Mouse'
        ], $this->search()->where(new MatchBoolean('firstName', 'Mickey'))->execute()['hits']['hits'][0]['_source']);
    }

    /**
     *
     */
    public function test_insert_same_id_twice()
    {
        $this->expectException(QueryExecutionException::class);
        $this->expectExceptionMessage('version conflict, document already exists');

        $this->query
            ->into('test_persons')
            ->bulk(false)
            ->values([
                '_id' => 1,
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ])
            ->execute()
        ;

        $this->query
            ->into('test_persons')
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
            ->into('test_persons')
            ->bulk(false)
            ->values([
                '_id' => 1,
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ])
            ->execute()
        ;

        $response = $this->query
            ->into('test_persons')
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

        $this->assertFalse($response->creation());
        $this->assertTrue($response->update());
        $this->assertFalse($response->deletion());
        $this->assertTrue($response->isWrite());
        $this->assertTrue($response->hasWrite());
        $this->assertFalse($response->isRead());
        $this->assertCount(1, $response);

        $this->assertEmpty(iterator_to_array($response));
        $this->assertEmpty($response->all());
        $this->assertSame($response, $response->asAssociative()->asList()->asObject()->asClass(\stdClass::class)->asColumn(1)->fetchMode(ResultSetInterface::FETCH_ASSOC));

        $this->assertEquals(1, $this->search()->execute()->total());
        $this->assertEquals([
            'firstName' => 'Minnie',
            'lastName' => 'Mouse'
        ], $this->search()->where(new MatchBoolean('firstName', 'Minnie'))->execute()['hits']['hits'][0]['_source']);
    }

    /**
     *
     */
    public function test_compile_simple_empty()
    {
        $this->expectException(InvalidQueryException::class);
        $this->expectExceptionMessage('No value to create');

        $this->query->into('test_persons', 'person')->bulk(false)->compile();
    }

    /**
     *
     */
    public function test_compile_simple()
    {
        $compiled = $this->query
            ->into('test_persons')
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
            ->into('test_persons')
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
                ['index' => ['_index' => 'test_persons']],
                [
                    'firstName' => 'Mickey',
                    'lastName' => 'Mouse'
                ],
                ['index' => ['_index' => 'test_persons']],
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
    public function test_compile_bulk_with_id()
    {
        $compiled = $this->query
            ->into('test_persons')
            ->values([
                '_id' => '1',
                'firstName' => 'Mickey',
                'lastName' => 'Mouse'
            ])
            ->values([
                '_id' => '2',
                'firstName' => 'Minnie',
                'lastName' => 'Mouse'
            ])
            ->compile()
        ;

        $this->assertEquals([
            'body' => [
                ['create' => ['_index' => 'test_persons', '_id' => '1']],
                [
                    'firstName' => 'Mickey',
                    'lastName' => 'Mouse'
                ],
                ['create' => ['_index' => 'test_persons', '_id' => '2']],
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
        $query = $this->query->into('test_persons');

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
                ['index' => ['_index' => 'test_persons']],
                [
                    'firstName' => 'Mickey',
                    'lastName' => 'Mouse'
                ]
            ]
        ], $compiled);
    }

    public function search(): ElasticsearchQuery
    {
        return (new ElasticsearchQuery(self::getClient()))->from('test_persons');
    }
}

class MyCustomResultClass
{
    public array $index;

    public function index()
    {
        return $this->index['_index'];
    }
}
