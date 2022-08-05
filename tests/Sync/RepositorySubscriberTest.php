<?php

namespace Bdf\Prime\Indexer\Sync;

use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\MatchBoolean;
use Bdf\Prime\Indexer\IndexInterface;
use Bdf\Prime\Indexer\Test\TestingIndexer;
use Bdf\Prime\Indexer\TestKernel;
use Bdf\Prime\Prime;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\Test\TestPack;
use ElasticsearchTestFiles\City;
use ElasticsearchTestFiles\CityIndex;
use PHPUnit\Framework\TestCase;

/**
 * Class RepositorySubscriberTest
 */
class RepositorySubscriberTest extends TestCase
{
    /**
     * @var TestKernel
     */
    private $app;

    /**
     * @var TestPack
     */
    private $testPack;

    /**
     * @var TestingIndexer
     */
    private $indexTester;

    /**
     * @var EntityRepository
     */
    private $repository;

    /**
     * @var IndexInterface
     */
    private $index;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new TestKernel('dev', false);
        $this->app->boot();

        $this->indexTester = new TestingIndexer($this->app->getContainer());
        $this->index = $this->indexTester->index(City::class);

        Prime::configure($this->app->getContainer()->get('prime'));

        $this->testPack = new TestPack();
        $this->testPack
            ->declareEntity(City::class)
            ->initialize()
        ;

        $this->indexTester = new TestingIndexer($this->app->getContainer());
        $this->index = $this->indexTester->index(City::class);

        (new RepositorySubscriber($this->app->getContainer()->get('messenger.default_bus'), City::class, new CityIndex()))
            ->subscribe($this->repository = Prime::repository(City::class));
    }

    protected function tearDown(): void
    {
        $this->indexTester->destroy();
        $this->testPack->destroy();
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

        $this->repository->insert($city);

        $this->assertTrue($this->index->contains($city));
        $this->index->refresh();

        $this->assertEquals([$city], $this->index->query()->where(new MatchBoolean('name', 'Paris'))->all());
    }

    /**
     *
     */
    public function test_insert_should_not_be_indexed()
    {
        $city = new City([
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR',
            'zipCode' => '75000',
            'enabled' => false,
        ]);

        $this->repository->insert($city);

        $this->assertFalse($this->index->contains($city));
    }

    /**
     *
     */
    public function test_update_not_yet_indexed_should_index_the_entity()
    {
        $city = new City([
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR',
            'zipCode' => '75000',
        ]);

        $this->repository->insert($city);
        $this->index->remove($city);

        $city->setPopulation(2500000);
        $this->repository->update($city);

        $this->assertTrue($this->index->contains($city));
        $this->index->refresh();

        $this->assertEquals([$city], $this->index->query()->where(new MatchBoolean('name', 'Paris'))->all());
    }

    /**
     *
     */
    public function test_update()
    {
        $city = new City([
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR',
            'zipCode' => '75000',
        ]);

        $this->repository->insert($city);

        $city->setPopulation(2500000);
        $this->repository->update($city);

        $this->assertTrue($this->index->contains($city));
        $this->index->refresh();

        $this->assertEquals([$city], $this->index->query()->where(new MatchBoolean('name', 'Paris'))->all());
    }

    /**
     *
     */
    public function test_update_should_not_be_indexed()
    {
        $city = new City([
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR',
            'zipCode' => '75000',
        ]);

        $this->repository->insert($city);

        $city->setEnabled(false);
        $this->repository->update($city);

        $this->assertFalse($this->index->contains($city));
    }

    /**
     *
     */
    public function test_delete()
    {
        $city = new City([
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR',
            'zipCode' => '75000',
        ]);

        $this->repository->insert($city);
        $this->repository->delete($city);

        $this->assertFalse($this->index->contains($city));
    }

    /**
     *
     */
    public function test_delete_already_deleted()
    {
        $city = new City([
            'name' => 'Paris',
            'population' => 2201578,
            'country' => 'FR',
            'zipCode' => '75000',
        ]);

        $this->repository->insert($city);
        $this->index->remove($city);
        $this->repository->delete($city);

        $this->assertFalse($this->index->contains($city));
    }
}
