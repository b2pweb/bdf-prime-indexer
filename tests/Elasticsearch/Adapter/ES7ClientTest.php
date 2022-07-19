<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Adapter;

use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\InvalidRequestException;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\NoNodeAvailableException;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\NotFoundException;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ES7ClientTest extends TestCase
{
    private ES7Client $client;

    protected function setUp(): void
    {
        if (!class_exists(Client::class)) {
            $this->markTestSkipped('"elasticsearch/elasticsearch" v7 is not installed at version 8');
        }

        $this->client = new ES7Client(ClientBuilder::fromConfig([
            'hosts' => [ELASTICSEARCH_HOST],
            'basicAuthentication' => [ELASTICSEARCH_USER, ELASTICSEARCH_PASSWORD],
        ]));
    }

    protected function tearDown(): void
    {
        $this->client->deleteAliases('test_index', ['foo', 'bar']);
        $this->client->deleteIndex('test_index');
        $this->client->deleteIndex('test_index2');
    }

    public function test_getInternalClient()
    {
        $this->assertInstanceOf(Client::class, $this->client->getInternalClient());
    }

    public function test_hasAlias()
    {
        $this->assertFalse($this->client->hasAlias('foo'));

        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
        $this->client->addAlias('test_index', 'foo');

        $this->assertTrue($this->client->hasAlias('foo'));
    }

    public function test_getAlias()
    {
        $this->assertNull($this->client->getAlias('foo'));

        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
        $this->client->addAlias('test_index', 'foo');

        $alias = $this->client->getAlias('foo');

        $this->assertSame(['foo'], $alias->all());
        $this->assertSame('test_index', $alias->index());

        $alias->delete();

        $this->assertNull($this->client->getAlias('foo'));
    }

    public function test_getAllAliases()
    {
        $this->assertArrayNotHasKey('test_index', $this->client->getAllAliases());
        $this->assertArrayNotHasKey('test_index2', $this->client->getAllAliases());

        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
        $this->client->createIndex('test_index2', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
        $this->client->addAlias('test_index', 'foo');
        $this->client->addAlias('test_index', 'bar');
        $this->client->addAlias('test_index2', 'baz');

        $aliases = $this->client->getAllAliases();

        $this->assertArrayHasKey('test_index', $aliases);
        $this->assertArrayHasKey('test_index2', $aliases);
        $this->assertEqualsCanonicalizing(['foo', 'bar'], $aliases['test_index']->all());
        $this->assertSame(['baz'], $aliases['test_index2']->all());
    }

    public function test_getAllAliases_with_name()
    {
        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
        $this->client->createIndex('test_index2', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
        $this->client->addAlias('test_index', 'foo');
        $this->client->addAlias('test_index', 'bar');
        $this->client->addAlias('test_index2', 'foo');

        $aliases = $this->client->getAllAliases('foo');

        $this->assertArrayHasKey('test_index', $aliases);
        $this->assertArrayHasKey('test_index2', $aliases);
        $this->assertSame(['foo'], $aliases['test_index']->all());
        $this->assertSame(['foo'], $aliases['test_index2']->all());
    }

    public function test_deleteAliases()
    {
        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
        $this->client->addAlias('test_index', 'foo');
        $this->client->addAlias('test_index', 'bar');
        $this->client->addAlias('test_index', 'baz');

        $this->client->deleteAliases('test_index', ['foo', 'bar']);
        $this->assertFalse($this->client->hasAlias('foo'));
        $this->assertFalse($this->client->hasAlias('bar'));
        $this->assertTrue($this->client->hasAlias('baz'));

        $this->client->deleteAliases('test_index', ['baz']);
        $this->assertFalse($this->client->hasAlias('baz'));

        $this->client->deleteAliases('test_index', ['foo', 'bar', 'baz']);
    }

    public function test_addAlias()
    {
        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
        $this->client->addAlias('test_index', 'foo');

        $this->assertTrue($this->client->hasAlias('foo'));
    }

    public function test_addAlias_already_exists()
    {
        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
        $this->client->addAlias('test_index', 'foo');
        $this->client->addAlias('test_index', 'foo');

        $this->assertTrue($this->client->hasAlias('foo'));
    }

    public function test_addAlias_invalid_index()
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('no such index [not_found]');

        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
        $this->client->addAlias('not_found', 'foo');
    }

    public function test_addAlias_with_name_already_taken_by_index()
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Invalid alias name [test_index]: an index or data stream exists with the same name as the alias');

        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
        $this->client->addAlias('test_index', 'test_index');
    }

    public function test_exists()
    {
        $this->assertFalse($this->client->exists('test_index', 'foo'));

        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
        $this->assertFalse($this->client->exists('test_index', 'foo'));

        $this->client->create('test_index', 'foo', ['foo' => 'bar'], true);
        $this->assertTrue($this->client->exists('test_index', 'foo'));
    }

    public function test_delete()
    {
        $this->assertFalse($this->client->delete('test_index', 'foo'));

        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
        $this->assertFalse($this->client->delete('test_index', 'foo'));

        $this->client->create('test_index', 'foo', ['foo' => 'bar'], true);
        $this->assertTrue($this->client->delete('test_index', 'foo'));

        $this->assertFalse($this->client->exists('test_index', 'foo'));
        $this->assertFalse($this->client->delete('test_index', 'foo'));
    }

    public function test_update()
    {
        $this->assertFalse($this->client->update('test_index', 'foo', ['doc' => ['foo' => 'rab']]));

        $this->client->create('test_index', 'foo', ['foo' => 'bar'], true);
        $this->assertTrue($this->client->update('test_index', 'foo', ['doc' => ['foo' => 'rab']]));
        $this->client->refreshIndex('test_index');

        $this->assertSame(['foo' => 'rab'], $this->client->search('test_index', [])->hits()[0]['_source']);
    }

    public function test_update_invalid_query()
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Validation Failed: 1: script or doc is missing');

        $this->assertFalse($this->client->update('test_index', 'foo', []));
    }

    public function test_index()
    {
        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);

        $res = $this->client->index('test_index', ['foo' => 'rab'], true);
        $doc = $this->client->search('test_index', [])->hits()[0];

        $this->assertSame('test_index', $res['_index']);
        $this->assertSame('created', $res['result']);
        $this->assertSame(1, $res['_version']);

        $this->assertEquals(['foo' => 'rab'], $doc['_source']);
        $this->assertEquals($res['_id'], $doc['_id']);
    }

    public function test_create()
    {
        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);

        $res = $this->client->create('test_index', 'foo', ['foo' => 'rab'], true);
        $doc = $this->client->search('test_index', [])->hits()[0];

        $this->assertSame('test_index', $res['_index']);
        $this->assertSame('created', $res['result']);
        $this->assertSame('foo', $res['_id']);
        $this->assertSame(1, $res['_version']);

        $this->assertEquals(['foo' => 'rab'], $doc['_source']);
        $this->assertEquals('foo', $doc['_id']);
    }

    public function test_create_already_exists()
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('version conflict, document already exists');

        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);

        $this->client->create('test_index', 'foo', ['foo' => 'rab'], true);
        $this->client->create('test_index', 'foo', ['foo' => 'rab'], true);
    }

    public function test_replace()
    {
        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);

        $res = $this->client->replace('test_index', 'foo', ['foo' => 'rab'], true);
        $doc = $this->client->search('test_index', [])->hits()[0];

        $this->assertSame('test_index', $res['_index']);
        $this->assertSame('created', $res['result']);
        $this->assertSame('foo', $res['_id']);
        $this->assertSame(1, $res['_version']);
        $this->assertEquals(['foo' => 'rab'], $doc['_source']);
        $this->assertEquals('foo', $doc['_id']);

        $res = $this->client->replace('test_index', 'foo', ['foo' => 'baz'], true);
        $doc = $this->client->search('test_index', [])->hits()[0];

        $this->assertSame('test_index', $res['_index']);
        $this->assertSame('updated', $res['result']);
        $this->assertSame('foo', $res['_id']);
        $this->assertSame(2, $res['_version']);
        $this->assertEquals(['foo' => 'baz'], $doc['_source']);
        $this->assertEquals('foo', $doc['_id']);
    }

    public function test_search_invalid_index()
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('no such index [invalid]');

        $this->client->search('invalid', []);
    }

    public function test_search_invalid_query()
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('parsing_exception');

        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);

        $this->client->search('test_index', ['$invalid']);
    }

    public function test_search_success()
    {
        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
        $this->client->index('test_index', ['foo' => 'rab'], true);
        $this->client->index('test_index', ['foo' => 'bar'], true);

        $results = $this->client->search('test_index', ['query' => ['match' => ['foo' => 'bar']]]);

        $this->assertEquals(1, $results->total());
        $this->assertTrue($results->isAccurateCount());
        $this->assertEquals(['foo' => 'bar'], $results->hits()[0]['_source']);
        $this->assertNull($results->scrollId());
        $this->assertGreaterThanOrEqual(0, $results->took());
        $this->assertFalse($results->timedOut());
        $this->assertEquals(['total' => 1, 'successful' => 1, 'skipped' => 0, 'failed' => 0], $results->shards());
        $this->assertEqualsWithDelta(0.69, $results->maxScore(), 0.01);
    }

    public function test_bulk()
    {
        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
        $res = $this->client->bulk([
            ['index' => ['_index' => 'test_index']],
            ['foo' => 'bar'],
            ['index' => ['_index' => 'test_index']],
            ['foo' => 'baz'],
        ], true);

        $this->assertCount(2, $res['items']);

        $this->assertEquals(['bar', 'baz'], array_map(fn ($doc) => $doc['_source']['foo'], $this->client->search('test_index', [])->hits()));
    }

    public function test_bulk_bad_request()
    {
        $this->expectException(InvalidRequestException::class);
        $this->client->bulk(['invalid']);
    }

    public function test_deleteIndex()
    {
        $this->assertFalse($this->client->deleteIndex('test_index'));

        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
        $this->assertTrue($this->client->hasIndex('test_index'));

        $this->assertTrue($this->client->deleteIndex('test_index'));
        $this->assertFalse($this->client->hasIndex('test_index'));
    }

    public function test_deleteIndex_multiple()
    {
        $this->assertTrue($this->client->deleteIndex('other', 'test_index', 'test_index2'));

        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
        $this->client->createIndex('test_index2', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);

        $this->assertTrue($this->client->deleteIndex('other', 'test_index', 'test_index2'));
        $this->assertFalse($this->client->hasIndex('test_index'));
        $this->assertFalse($this->client->hasIndex('test_index2'));
    }

    public function test_refreshIndex_not_found()
    {
        $this->expectException(NotFoundException::class);

        $this->client->refreshIndex('not_found');
    }

    public function test_createIndex_error_already_exists()
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('already exists');

        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
    }

    public function test_createIndex_invalid_request()
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('unknown key [foo] for create index');

        $this->client->createIndex('test_index', ['foo' => []]);
    }

    public function test_createIndex_success()
    {
        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
        $this->assertTrue($this->client->hasIndex('test_index'));
    }

    public function test_getAllIndexes()
    {
        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);
        $this->client->createIndex('test_index2', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);

        $indexes = $this->client->getAllIndexes();

        $this->assertContains('test_index', $indexes);
        $this->assertContains('test_index2', $indexes);
    }

    public function test_getAllIndexesMapping()
    {
        $this->client->createIndex('test_index', ['mappings' => ['properties' => ['foo' => ['type' => 'text']]]]);

        $indexes = $this->client->getAllIndexesMapping();

        $this->assertArrayHasKey('test_index', $indexes);
        $this->assertEquals(['mappings' => ['properties' => ['foo' => ['type' => 'text']]]], $indexes['test_index']);
    }

    public function test_info()
    {
        $this->assertNotEmpty($this->client->info());
    }

    public function test_NoNodeAvailableException_error()
    {
        $this->expectException(NoNodeAvailableException::class);
        $client = new ES7Client(ClientBuilder::fromConfig([
            'hosts' => ['127.0.0.15'],
        ]));

        $client->info();
    }
}
