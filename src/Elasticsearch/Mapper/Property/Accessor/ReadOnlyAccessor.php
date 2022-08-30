<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor;

/**
 * Accessor for only read from model (do not hydrate entity with indexed value)
 */
final class ReadOnlyAccessor implements PropertyAccessorInterface
{
    /**
     * @var PropertyAccessorInterface
     */
    private PropertyAccessorInterface $inner;


    /**
     * ReadOnlyAccessor constructor.
     *
     * @param PropertyAccessorInterface|string $inner The inner accessor, or the property name
     */
    public function __construct($inner)
    {
        $this->inner = is_string($inner) ? new SimplePropertyAccessor($inner) : $inner;
    }

    /**
     * {@inheritdoc}
     */
    public function readFromModel($entity)
    {
        return $this->inner->readFromModel($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function writeToModel($entity, $indexedValue): void
    {
    }
}
