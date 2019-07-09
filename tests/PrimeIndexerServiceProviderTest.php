<?php

namespace Bdf\Prime\Indexer;

use Bdf\Config\Config;
use Bdf\Prime\Indexer\Elasticsearch\ElasticsearchIndex;
use Bdf\Prime\PrimeServiceProvider;
use Bdf\Web\Application;
use Elasticsearch\Client;
use PHPUnit\Framework\TestCase;

/**
 * Class PrimeIndexerServiceProviderTest
 */
class PrimeIndexerServiceProviderTest extends TestCase
{
    /**
     * @var Application
     */
    private $app;

    protected function setUp()
    {
        $this->app = new Application([
            'config' => new Config([
                'elasticsearch' => [
                    'hosts' => ['127.0.0.1:9200'],
                ],
            ]),
        ]);
        $this->app->register(new PrimeServiceProvider());
    }

    /**
     *
     */
    public function test_instances()
    {
        $this->app->register(new PrimeIndexerServiceProvider());

        $this->assertInstanceOf(Client::class, $this->app->get(Client::class));
        $this->assertInstanceOf(IndexFactory::class, $this->app->get(IndexFactory::class));
    }

    /**
     *
     */
    public function test_with_indexes_configuration()
    {
        $this->app->set('prime.indexes', [
            \User::class => new \UserIndex()
        ]);
        $this->app->register(new PrimeIndexerServiceProvider());

        $factory = $this->app->get(IndexFactory::class);
        $index = $factory->for(\User::class);

        $this->assertInstanceOf(ElasticsearchIndex::class, $index);
    }
}
