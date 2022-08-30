<?php

namespace Bdf\Prime\Indexer\Test;

use Bdf\Collection\HashSet;
use Bdf\Collection\SetInterface;
use Bdf\Collection\Util\Functor\Consumer\Call;
use Bdf\Collection\Util\Functor\Predicate\IsInstanceOf;
use Bdf\Prime\Indexer\Elasticsearch\ElasticsearchIndex;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\IndexFactory;
use Bdf\Prime\Indexer\IndexInterface;
use Psr\Container\ContainerInterface;
use ReflectionProperty;

/**
 * Testing tool for setUp and use indexes
 */
class TestingIndexer
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var IndexFactory|null
     */
    private ?IndexFactory $factory = null;

    /**
     * Set of initialized indexes
     *
     * @var SetInterface|IndexInterface[]
     * @psalm-var SetInterface<IndexInterface>
     */
    private SetInterface $indexes;

    /**
     * @var array|null
     */
    private ?array $lastIndexesConfigurations = null;

    /**
     * @var ReflectionProperty|null
     */
    private ?ReflectionProperty $configProperty = null;

    /**
     * @var ReflectionProperty|null
     */
    private ?ReflectionProperty $indexesProperty = null;


    /**
     * TestingIndexer constructor.
     *
     * @param ContainerInterface $app
     */
    public function __construct(ContainerInterface $app)
    {
        $this->container = $app;
        $this->indexes = HashSet::spl();
    }

    /**
     * Drop all indexes and restore the IndexFactory
     */
    public function destroy(): void
    {
        $this->indexes->forEach(new Call('drop', []));
        $this->indexes->clear();

        if ($this->lastIndexesConfigurations) {
            $this->setConfigurations($this->lastIndexesConfigurations);
            $this->resetIndexesProperty();
            $this->lastIndexesConfigurations = null;
            $this->factory = null;
        }
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
        $this->execute($entities, function (IndexInterface $index, object $entity) {
            $index->add($entity);
        });

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
        $this->execute($entities, function (IndexInterface $index, object $entity) {
            $index->remove($entity);
        });

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
     * Execute an action on an index, on each entity
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

        $this->factory = $this->container->get(IndexFactory::class);
        $this->lastIndexesConfigurations = $this->getConfigurations();

        $this->setConfigurations(array_map([$this, 'toTestingConfiguration'], $this->lastIndexesConfigurations));
        $this->resetIndexesProperty();

        return $this->factory;
    }

    private function configurationsProperty(): ReflectionProperty
    {
        if ($this->configProperty) {
            return $this->configProperty;
        }

        $this->configProperty = new ReflectionProperty($this->factory, 'configurations');
        $this->configProperty->setAccessible(true);

        return $this->configProperty;
    }

    private function getConfigurations(): array
    {
        assert(!!$this->factory);
        return $this->configurationsProperty()->getValue($this->factory);
    }

    private function setConfigurations(array $configurations): void
    {
        assert(!!$this->factory);
        $this->configurationsProperty()->setValue($this->factory, $configurations);
    }

    private function resetIndexesProperty(): void
    {
        if (!$this->indexesProperty) {
            $this->indexesProperty = new ReflectionProperty($this->factory, 'indexes');
            $this->indexesProperty->setAccessible(true);
        }

        $this->indexesProperty->setValue($this->factory, []);
    }

    private static function toTestingConfiguration(object $config): object
    {
        if ($config instanceof ElasticsearchIndexConfigurationInterface) {
            return new ElasticsearchTestingIndexConfig($config);
        }

        throw new \LogicException('Unsupported config ' . get_class($config));
    }
}
