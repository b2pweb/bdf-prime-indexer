<?php

namespace Bdf\Prime\Indexer\Sync;

use Bdf\Bus\MessageDispatcherInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\Match;
use Bdf\Prime\Indexer\IndexInterface;
use Bdf\Prime\Indexer\Test\TestingIndexer;
use Bdf\Prime\Indexer\TestKernel;
use ElasticsearchTestFiles\City;
use PHPUnit\Framework\TestCase;

/**
 * Class AddToIndexTest
 */
class AddToIndexTest extends TestCase
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
    public function test_insert()
    {
        $city = new City([
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR',
            'zipCode' => '75000'
        ]);

        $this->bus->dispatch(new AddToIndex(City::class, $city));

        $this->assertTrue($this->index->contains($city));
        $this->index->refresh();

        $this->assertEquals([$city], $this->index->query()->where(new Match('name', 'Paris'))->all());
    }
}
