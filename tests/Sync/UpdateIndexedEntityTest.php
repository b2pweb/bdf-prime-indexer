<?php

namespace Bdf\Prime\Indexer\Sync;

use Bdf\Bus\MessageDispatcherInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\MatchBoolean;
use Bdf\Prime\Indexer\IndexInterface;
use Bdf\Prime\Indexer\Test\TestingIndexer;
use Bdf\Prime\Indexer\TestKernel;
use ElasticsearchTestFiles\City;
use PHPUnit\Framework\TestCase;

/**
 * Class UpdateIndexedEntityTest
 */
class UpdateIndexedEntityTest extends TestCase
{
    /**
     * @var TestKernel
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

        $this->app = new TestKernel('dev', false);
        $this->app->boot();

        $this->indexTester = new TestingIndexer($this->app->getContainer());
        $this->index = $this->indexTester->index(City::class);

        $this->bus = $this->app->getContainer()->get('messenger.default_bus');
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

        $this->assertEquals([$city], $this->index->query()->where(new MatchBoolean('name', 'Paris'))->all());
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

        $this->assertEquals([$city], $this->index->query()->where(new MatchBoolean('name', 'Paris'))->all());
    }
}
