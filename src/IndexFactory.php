<?php

namespace Bdf\Prime\Indexer;

use Bdf\Prime\Indexer\Exception\IndexNotFoundException;
use Bdf\Prime\Indexer\Exception\InvalidIndexConfigurationException;
use Bdf\Prime\Indexer\Resolver\IndexResolverInterface;

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
     * @param IndexResolverInterface $resolver
     */
    public function __construct(array $factories, IndexResolverInterface $resolver)
    {
        $this->factories = $factories;
        $this->resolver = $resolver;
    }

    /**
     * Get the index for the given entity class
     *
     * @param class-string<E> $entity The entity class name
     *
     * @return IndexInterface<E>
     *
     * @throws IndexNotFoundException When cannot found any valid index configuration for the requested entity
     * @throws InvalidIndexConfigurationException When cannot create the index using the resolved configuration
     *
     * @template E as object
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

        throw new InvalidIndexConfigurationException($entity, $configuration);
    }
}
