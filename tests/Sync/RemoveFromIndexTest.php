<?php

namespace Bdf\Prime\Indexer\Sync;

use Bdf\Bus\BusServiceProvider;
use Bdf\Bus\MessageDispatcherInterface;
use Bdf\Config\Config;
use Bdf\Prime\Indexer\IndexInterface;
use Bdf\Prime\Indexer\PrimeIndexerServiceProvider;
use Bdf\Prime\Indexer\Test\TestingIndexer;
use Bdf\Prime\PrimeServiceProvider;
use Bdf\Web\Application;
use City;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Class RemoveFromIndexTest
 */
class RemoveFromIndexTest extends TestCase
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var TestingIndexer
     */
    private $indexTester;

    /**
     * @var IndexInterface
     */
    private $index;

    /**
     * @var MessageDispatcherInterface
     */
    private $bus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Application([
            'config' => new Config([
                'elasticsearch' => ['hosts' => ['127.0.0.1:9222']]
            ]),
            'prime.indexes' => [
                \City::class => new \CityIndex(),
            ],
            'logger' => new NullLogger()
        ]);
        $this->app->register(new BusServiceProvider());
        $this->app->register(new PrimeServiceProvider());
        $this->app->register(new PrimeIndexerServiceProvider());

        $this->indexTester = new TestingIndexer($this->app);
        $this->index = $this->indexTester->index(\City::class);

        $this->bus = $this->app['bus.dispatcher'];
    }

    protected function tearDown(): void
    {
        $this->indexTester->destroy();
    }

    /**
     *
     */
    public function test_execute()
    {
        $this->indexTester->push($city = new City([
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR',
            'zipCode' => '75000'
        ]));

        $this->bus->dispatch(new RemoveFromIndex(City::class, $city));

        $this->assertFalse($this->index->contains($city));
    }

    /**
     *
     */
    public function test_execute_not_indexed()
    {
        $city = new City([
            'id' => 1,
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR',
            'zipCode' => '75000'
        ]);

        $this->bus->dispatch(new RemoveFromIndex(City::class, $city));

        $this->assertFalse($this->index->contains($city));
    }
}
