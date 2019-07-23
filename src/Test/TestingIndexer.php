<?php

namespace Bdf\Prime\Indexer\Test;

use Bdf\Collection\HashSet;
use Bdf\Collection\SetInterface;
use Bdf\Collection\Util\Functor\Consumer\Call;
use Bdf\Collection\Util\Functor\Predicate\IsInstanceOf;
use Bdf\PHPUnit\Extensions\AppServiceStack;
use Bdf\Prime\Indexer\Elasticsearch\ElasticsearchIndex;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\IndexFactory;
use Bdf\Prime\Indexer\IndexInterface;
use Bdf\Web\Application;

/**
 * Testing tool for setUp and use indexes
 */
class TestingIndexer
{
    use AppServiceStack;

    /**
     * @var Application
     */
    private $app;

    /**
     * @var IndexFactory
     */
    private $factory;

    /**
     * Set of initialized indexes
     *
     * @var SetInterface|IndexInterface[]
     */
    private $indexes;


    /**
     * TestingIndexer constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->indexes = HashSet::spl();
    }

    /**
     * Drop all indexes and restore the IndexFactory
     */
    public function destroy(): void
    {
        $this->indexes->forEach(new Call('drop', []));
        $this->indexes->clear();

        $this->restoreApplicationServices($this->app);
    }

    /**
     * Push one or more entities to the index
     *
     * @param object|object[] $entities
     *
     * @return $this
     */
    public function push($entities): TestingIndexer
    {
        $this->execute($entities, function (IndexInterface $index, $entity) { $index->add($entity); });

        return $this;
    }

    /**
     * Remove entities from index
     *
     * @param object|object[] $entities
     *
     * @return $this
     */
    public function remove($entities)
    {
        $this->execute($entities, function (IndexInterface $index, $entity) { $index->remove($entity); });

        return $this;
    }

    /**
     * Get the index for the given entity
     *
     * Creates the index, if not yet created
     *
     * @param string|object $entity The entity class, or object
     *
     * @return IndexInterface
     */
    public function index($entity): IndexInterface
    {
        if (is_object($entity)) {
            $entity = get_class($entity);
        }

        $index = $this->factory()->for($entity);

        if (!$this->indexes->contains($index)) {
            $index->create([]);
            $this->indexes->add($index);
        }

        return $index;
    }

    /**
     * Destroy one object destruct
     */
    public function __destruct()
    {
        $this->destroy();
    }

    /**
     * Execute an action on an index, on each entities
     *
     * @param object|object[] $entities
     * @param callable $action
     */
    private function execute($entities, callable $action): void
    {
        if (!is_array($entities)) {
            $entities = [$entities];
        }

        $indexes = new HashSet('spl_object_hash');

        foreach ($entities as $entity) {
            $indexes->add($index = $this->index(get_class($entity)));
            $action($index, $entity);
        }

        $indexes
            ->stream()
            ->filter(new IsInstanceOf(ElasticsearchIndex::class))
            ->forEach(new Call('refresh', []))
        ;
    }

    /**
     * Get the testing index factory
     *
     * @return IndexFactory
     */
    private function factory(): IndexFactory
    {
        if ($this->factory) {
            return $this->factory;
        }

        $this->factory = new IndexFactory(
            $this->app['prime.index.factories'],
            array_map([$this, 'toTestingConfiguration'], $this->app->has('prime.indexes') ? $this->app->get('prime.indexes') : [])
        );

        $this->storeApplicationService($this->app, IndexFactory::class, $this->factory);

        return $this->factory;
    }

    private static function toTestingConfiguration($config)
    {
        if ($config instanceof ElasticsearchIndexConfigurationInterface) {
            return new ElasticsearchTestingIndexConfig($config);
        }

        throw new \LogicException('Unsupported config '.get_class($config));
    }
}
