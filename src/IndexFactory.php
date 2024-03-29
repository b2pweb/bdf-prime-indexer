<?php

namespace Bdf\Prime\Indexer;

use Bdf\Prime\Indexer\Exception\IndexNotFoundException;
use Bdf\Prime\Indexer\Resolver\IndexResolverInterface;
use Bdf\Prime\Indexer\Resolver\MappingResolver;
use Psr\Container\ContainerInterface;
use Bdf\Prime\Indexer\Exception\IndexConfigurationException;

/**
 * Creates indexes
 */
class IndexFactory
{
    /**
     * @var array<class-string, callable(object, IndexFactory):IndexInterface>
     */
    private $factories;

    /**
     * @var IndexResolverInterface
     */
    private $resolver;

    /**
     * Index instances, by entity class name
     *
     * @var array<class-string, IndexInterface>
     */
    private $indexes = [];


    /**
     * IndexerFactory constructor.
     *
     * @param array<class-string, callable(object, IndexFactory):IndexInterface> $factories
     * @param IndexResolverInterface|array<class-string, IndexConfigurationInterface> $resolver
     */
    public function __construct(array $factories, /*IndexResolverInterface */$resolver)
    {
        if (is_array($resolver)) {
            @trigger_error('Passing array of configuration at second parameter of ' . __METHOD__ . ' is deprecated since 2.0', E_USER_DEPRECATED);
            $resolver = new MappingResolver(
                new class implements ContainerInterface {
                    public function get(string $id)
                    {
                        return null;
                    }

                    public function has(string $id): bool
                    {
                        return false;
                    }
                },
                $resolver
            );
        }

        $this->factories = $factories;
        $this->resolver = $resolver;
    }

    /**
     * Create the index for the given entity
     *
     * @param class-string $entity Entity class name
     *
     * @return IndexInterface
     * @throws IndexConfigurationException When cannot find any valid configuration for the given entity
     */
    public function for(string $entity): IndexInterface
    {
        if (isset($this->indexes[$entity])) {
            return $this->indexes[$entity];
        }

        $configuration = $this->resolver->resolve($entity);

        if (!$configuration) {
            throw new IndexNotFoundException($entity);
        }

        foreach ($this->factories as $name => $factory) {
            if ($configuration instanceof $name) {
                return $this->indexes[$entity] = $factory($configuration, $this);
            }
        }

        throw new IndexConfigurationException('Cannot found any factory for configuration '.get_class($configuration));
    }

    /**
     * Register a new entity in the indexer system
     *
     * @param class-string $entity The entity class name
     * @param object $config The index configuration
     *
     * @deprecated Since 2.0. Inject IndexResolverInterface at constructor instead.
     */
    public function register(string $entity, object $config): void
    {
        if (!$this->resolver instanceof MappingResolver) {
            throw new \LogicException('Cannot call register on the given IndexResolverInterface instance.');
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        $this->resolver->register($config, $entity);
    }
}
