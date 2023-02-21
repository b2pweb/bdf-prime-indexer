<?php

namespace Bdf\Prime\Indexer\Bundle;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Exception\QueryExecutionException;
use Bdf\Prime\Indexer\IndexFactory;
use Bdf\Prime\Indexer\KernelWithTestBundle;
use Bdf\Prime\Indexer\Test\TestingIndexer;
use ElasticsearchTestFiles\City;
use PHPUnit\Framework\TestCase;

class PrimeIndexerTestBundleTest extends TestCase
{
    public function test_instance()
    {
        $kernel = new KernelWithTestBundle('dev', true);
        $kernel->boot();

        $this->assertInstanceOf(TestingIndexer::class, $kernel->getContainer()->get(TestingIndexer::class));
    }

    public function test_boot_should_init_testing_indexer()
    {
        $kernel = new KernelWithTestBundle('dev', true);
        $kernel->boot();

        /** @var IndexFactory $factory */
        $factory = $kernel->getContainer()->get(IndexFactory::class);
        $this->assertInstanceOf(ElasticsearchIndexConfigurationInterface::class, $factory->for(City::class)->config());

        $testing = $kernel->getContainer()->get(TestingIndexer::class);
        $testing->push([
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
        ]);

        $this->assertCount(2, $factory->for(City::class)->query()->all());

        $kernel->shutdown();

        try {
            $factory->for(City::class)->query()->all();
            $this->fail('QueryExecutionException should be thrown');
        } catch (QueryExecutionException $e) {
            $this->assertStringContainsString('no such index [test_cities]', $e->getMessage());
        }
    }
}
