<?php

namespace Bdf\Prime\Indexer\Sync;

use Bdf\Bus\MessageDispatcherInterface;
use Bdf\Prime\Indexer\IndexInterface;
use Bdf\Prime\Indexer\Test\TestingIndexer;
use Bdf\Prime\Indexer\TestKernel;
use City;
use PHPUnit\Framework\TestCase;

/**
 * Class RemoveFromIndexTest
 */
class RemoveFromIndexTest extends TestCase
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
        $this->index = $this->indexTester->index(\City::class);

        $this->bus = $this->app->getContainer()->get('messenger.default_bus');
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
