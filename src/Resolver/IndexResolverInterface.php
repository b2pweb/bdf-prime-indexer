<?php

namespace Bdf\Prime\Indexer\Resolver;

use Bdf\Prime\Indexer\IndexConfigurationInterface;

/**
 * Resolver for index configuration
 */
interface IndexResolverInterface
{
    /**
     * Resolve the index configuration from the entity class name
     *
     * @param class-string<E> $entity
     *
     * @return IndexConfigurationInterface<E>|null The configuration instance if found
     * @template E as object
     */
    public function resolve(string $entity): ?IndexConfigurationInterface;
}
