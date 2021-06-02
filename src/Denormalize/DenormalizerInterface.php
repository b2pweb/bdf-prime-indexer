<?php

namespace Bdf\Prime\Indexer\Denormalize;

use Bdf\Prime\Indexer\IndexConfigurationInterface;

/**
 * Process denormalization of an entity for indexing
 *
 * @template E as object
 * @template D as object
 *
 * @extends IndexConfigurationInterface<E>
 */
interface DenormalizerInterface extends IndexConfigurationInterface
{
    /**
     * Denormalize the entity (i.e. convert DB entity to indexed entity)
     *
     * @param E $entity Entity to denormalize
     *
     * @return D Denormalized object
     */
    public function denormalize($entity);

    /**
     * Get the denormalized class (i.e. the indexed entity)
     *
     * @return class-string<D>
     */
    public function denormalizedClass(): string;
}
