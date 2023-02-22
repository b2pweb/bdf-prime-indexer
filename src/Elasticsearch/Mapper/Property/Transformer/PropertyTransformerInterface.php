<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Transformer;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\AnalyzerInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertyInterface;

/**
 * Transformer for a single indexed property
 *
 * Used for transform and extract value between model and index
 * Its use before {@see AnalyzerInterface::toIndex()} and after {@see AnalyzerInterface::fromIndex()}
 */
interface PropertyTransformerInterface
{
    /**
     * Transform a PHP value to indexed one
     *
     * @param PropertyInterface $property The property storing the value
     * @param mixed $value The PHP value
     *
     * @return mixed
     */
    public function toIndex(PropertyInterface $property, $value);

    /**
     * Transform an indexed value to PHP one
     *
     * @param PropertyInterface $property The property storing the value
     * @param mixed $value The indexed value
     *
     * @return mixed
     */
    public function fromIndex(PropertyInterface $property, $value);
}
