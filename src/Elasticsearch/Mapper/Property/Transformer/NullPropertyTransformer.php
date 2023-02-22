<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Transformer;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertyInterface;

/**
 * Null object for {@see PropertyTransformerInterface}
 * This implementation does nothing (i.e. return the same value)
 */
final class NullPropertyTransformer implements PropertyTransformerInterface
{
    private static ?self $instance = null;

    /**
     * {@inheritdoc}
     */
    public function toIndex(PropertyInterface $property, $value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function fromIndex(PropertyInterface $property, $value)
    {
        return $value;
    }

    /**
     * Get the singleton instance
     *
     * @return self
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }
}
