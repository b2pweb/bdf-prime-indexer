<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Property;


use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\AnalyzerInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;

/**
 * Base type for a document property
 * This property can be a nested object or a scalar property
 */
interface PropertyInterface extends PropertyAccessorInterface
{
    /**
     * Get the indexed property name
     *
     * @return string
     */
    public function name(): string;

    /**
     * Get the property declaration custom settings (excluding type and analyzer)
     *
     * @return array
     */
    public function declaration(): array;

    /**
     * Get the property index type
     *
     * @return string
     */
    public function type(): string;

    /**
     * Get the accessor related to the property
     *
     * @return PropertyAccessorInterface
     */
    public function accessor(): PropertyAccessorInterface;
}
