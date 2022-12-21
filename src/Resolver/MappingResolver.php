<?php

namespace Bdf\Prime\Indexer\Resolver;

use Bdf\Prime\Indexer\IndexConfigurationInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

/**
 * Index configuration resolver using simple mapping
 */
final class MappingResolver implements IndexResolverInterface
{
    /**
     * @var array<class-string, class-string<IndexConfigurationInterface>|IndexConfigurationInterface>
     * @psalm-var class-string-map<E, class-string<IndexConfigurationInterface<E>>|IndexConfigurationInterface<E>>
     */
    private array $mapping = [];

    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;


    /**
     * MappingResolver constructor.
     *
     * @param ContainerInterface $container
     * @param array<class-string|int, IndexConfigurationInterface|class-string<IndexConfigurationInterface>> $mapping
     */
    public function __construct(ContainerInterface $container, array $mapping = [])
    {
        $this->container = $container;

        foreach ($mapping as $entity => $configuration) {
            $this->register($configuration, is_string($entity) ? $entity : null);
        }
    }

    /**
     * Register a new index configuration
     *
     * @param class-string<IndexConfigurationInterface<E>>|IndexConfigurationInterface<E> $configuration The configuration class name or instance
     * @param class-string<E>|null $entityClassName
     *
     * @template E as object
     */
    public function register($configuration, ?string $entityClassName = null): void
    {
        if (!$entityClassName) {
            if (!$configuration instanceof IndexConfigurationInterface) {
                throw new InvalidArgumentException('$entityClassName is required when passing a string as first parameter');
            }

            $entityClassName = $configuration->entity();
        }

        /** @psalm-suppress InvalidPropertyAssignmentValue */
        $this->mapping[$entityClassName] = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $entity): ?IndexConfigurationInterface
    {
        if (!isset($this->mapping[$entity])) {
            return null;
        }

        $config = $this->mapping[$entity];

        if (is_string($config)) {
            return $this->container->get($config);
        }

        return $config;
    }
}
