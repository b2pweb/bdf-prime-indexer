<?php

namespace Bdf\Prime\Indexer\Test;

use Bdf\Prime\Indexer\Elasticsearch\ElasticsearchIndex;
use Bdf\Prime\Indexer\TestKernel;
use Elasticsearch\Client;
use ElasticsearchTestFiles\City;
use ElasticsearchTestFiles\User;
use PHPUnit\Framework\TestCase;

/**
 * Class TestingIndexerTest
 */
class TestingIndexerTest extends TestCase
{
    /**
     * @var TestingIndexer
     */
    private $indexer;

    /**
     * @var TestKernel
     */
    private $app;

    /**
     * @var Client
     */
    private $client;

    protected function setUp(): void
    {
        $this->app = new TestKernel('dev', false);
        $this->app->boot();

        $this->client = $this->app->getContainer()->get(Client::class);
        $this->indexer = new TestingIndexer($this->app->getContainer());
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->indexer->destroy();
        $this->indexer = null;
    }

    /**
     *
     */
    public function test_index_should_create_the_index_with_prefix()
    {
        $index = $this->indexer->index(User::class);

        $this->assertInstanceOf(ElasticsearchIndex::class, $index);
        $this->assertInstanceOf(ElasticsearchTestingIndexConfig::class, $index->config());

        $this->assertTrue($this->client->indices()->existsAlias(['name' => 'test_test_users']));
        $this->assertFalse($this->client->indices()->existsAlias(['name' => 'test_users']));
    }

    /**
     *
     */
    public function test_destroy_should_drop_indexes()
    {
        $this->indexer->index(User::class);
        $this->indexer->index(City::class);

        $this->indexer->destroy();

        $this->assertFalse($this->client->indices()->existsAlias(['name' => 'test_test_users']));
        $this->assertFalse($this->client->indices()->existsAlias(['name' => 'test_test_cities']));
    }

    /**
     *
     */
    public function test_push_should_create_index_and_store_the_entity()
    {
        $this->indexer->push($city = new City([
            'id' => 1,
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR',
            'zipCode' => '75000'
        ]));

        $this->assertEquals($city, $this->indexer->index(City::class)->query()->first()->get());
    }

    /**
     *
     */
    public function test_push_with_array()
    {
        $this->indexer->push([
            new City([
                'name' => 'Paris',
                'population' => 2201578,
                'country' => 'FR',
                'zipCode' => '75000',
            ]),
            new City([
                'name' => 'Paris',
                'population' => 27022,
                'country' => 'US',
                'zipCode' => '75460',
            ]),
            new City([
                'name' => 'Parthenay',
                'population' => 11599,
                'country' => 'FR',
                'zipCode' => '79200',
            ]),
            new City([
                'name' => 'Cavaillon',
                'population' => 26689,
                'country' => 'FR',
                'zipCode' => '84300',
            ]),
            new City([
                'name' => 'Disabled',
                'population' => 0,
                'country' => 'FR',
                'zipCode' => '000000',
                'enabled' => false,
            ]),
        ]);

        $this->assertCount(4, $this->indexer->index(City::class)->query()->all());
    }

    /**
     *
     */
    public function test_remove()
    {
        $this->indexer->push($city = new City([
            'id' => 1,
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR',
            'zipCode' => '75000'
        ]));

        $this->indexer->remove($city);

        $this->assertFalse($this->indexer->index($city)->contains($city));
    }
}
