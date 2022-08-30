<?php

namespace Bdf\Prime\Indexer;

use Bdf\Prime\Indexer\Exception\IndexConfigurationException;

/**
 * Creates indexes
 */
class IndexFactory
{
    /**
     * @var array<class-string, callable(object):IndexInterface>
     */
    private array $factories = [];

    /**
     * @var array<string, object>
     */
    private array $configurations = [];

    /**
     * Index instances, by entity class name
     *
     * @var array<string, IndexInterface>
     */
    private array $indexes = [];


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
     * @throws IndexConfigurationException When cannot find any valid configuration for the given entity
     */
    public function for(string $entity): IndexInterface
    {
        if (isset($this->indexes[$entity])) {
            return $this->indexes[$entity];
        }

        $configuration = $this->configurations[$entity] ?? null;

        if (!$configuration) {
            throw new IndexConfigurationException('Cannot found a configuration for entity ' . $entity);
        }

        foreach ($this->factories as $name => $factory) {
            if ($configuration instanceof $name) {
                return $this->indexes[$entity] = $factory($configuration);
            }
        }

        throw new IndexConfigurationException('Cannot found any factory for configuration '.get_class($configuration));
    }

    /**
     * Register a new entity in the indexer system
     *
     * @param string $entity The entity class name
     * @param object $config The index configuration
     */
    public function register(string $entity, object $config): void
    {
        $this->configurations[$entity] = $config;
    }
}
