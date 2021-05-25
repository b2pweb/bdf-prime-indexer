<?php

namespace Bdf\Prime\Indexer;

/**
 * Creates indexes
 */
class IndexFactory
{
    /**
     * @var callable[]
     */
    private $factories = [];

    /**
     * @var array
     */
    private $configurations = [];

    /**
     * Index instances, by entity class name
     *
     * @var IndexInterface[]
     */
    private $indexes = [];


    /**
     * IndexerFactory constructor.
     *
     * @param callable[] $factories
     * @param array $configurations
     */
    public function __construct(array $factories, array $configurations)
    {
        $this->factories = $factories;
        $this->configurations = $configurations;
    }

    /**
     * @param string $entity
     *
     * @return IndexInterface
     */
    public function for(string $entity): IndexInterface
    {
        if (isset($this->indexes[$entity])) {
            return $this->indexes[$entity];
        }

        $configuration = $this->configurations[$entity];

        foreach ($this->factories as $name => $factory) {
            if ($configuration instanceof $name) {
                return $this->indexes[$entity] = $factory($configuration);
            }
        }

        throw new \LogicException('Cannot found any factory for configuration '.get_class($configuration));
    }

    /**
     * Register a new entity in the indexer system
     *
     * @param string $entity The entity class name
     * @param object $config The index configuration
     */
    public function register(string $entity, $config): void
    {
        $this->configurations[$entity] = $config;
    }
}
