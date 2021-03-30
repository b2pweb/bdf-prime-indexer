<?php

namespace Bdf\Prime\Indexer\Sync;

use Bdf\Bus\BusServiceProvider;
use Bdf\Bus\MessageDispatcherInterface;
use Bdf\Config\Config;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\Match;
use Bdf\Prime\Indexer\IndexInterface;
use Bdf\Prime\Indexer\PrimeIndexerServiceProvider;
use Bdf\Prime\Indexer\Test\TestingIndexer;
use Bdf\Prime\PrimeServiceProvider;
use Bdf\Web\Application;
use City;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Class UpdateIndexedEntityTest
 */
class UpdateIndexedEntityTest extends TestCase
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
    public function test_not_indexed()
    {
        $city = new City([
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR',
            'zipCode' => '75000'
        ]);

        $this->bus->dispatch(new UpdateIndexedEntity(City::class, $city));

        $this->assertTrue($this->index->contains($city));
        $this->index->refresh();

        $this->assertEquals([$city], $this->index->query()->where(new Match('name', 'Paris'))->all());
    }

    /**
     *
     */
    public function test_indexed()
    {
        $this->indexTester->push($city = new City([
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR',
            'zipCode' => '75000'
        ]));

        $city->setPopulation(2500000);

        $this->bus->dispatch(new UpdateIndexedEntity(City::class, $city));
        $this->index->refresh();

        $this->assertEquals([$city], $this->index->query()->where(new Match('name', 'Paris'))->all());
    }
}
