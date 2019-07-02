<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor;

/**
 * Accessor for a single indexed property
 * Used for transform and extract value between model and index
 */
interface PropertyAccessorInterface
{
    /**
     * Read (and transform) the value from model to be indexed
     *
     * @param object $entity The model entity (retreived from database)
     *
     * @return mixed The extract value
     */
    public function readFromModel($entity);

    /**
     * Write the indexed value to the model entity
     *
     * @param object $entity The entity object to write on
     * @param mixed $indexedValue The indexed value (retreived from elastricsearch)
     *
     * @return void
     */
    public function writeToModel($entity, $indexedValue);
}
