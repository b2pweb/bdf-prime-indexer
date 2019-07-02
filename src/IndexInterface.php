<?php

namespace Bdf\Prime\Indexer;

/**
 * Store entities for perform complex search
 */
interface IndexInterface
{
    /**
     * Store an entity to the index
     *
     * @param object $entity
     *
     * @return void
     */
    public function add($entity);

    /**
     * Remove the entity from the index
     *
     * @param object $entity
     *
     * @return void
     */
    public function remove($entity);

    /**
     * Replace the entity data from the index
     *
     * @param object $entity
     *
     * @return void
     */
    public function update($entity);

    /**
     * Get the search query
     *
     * <code>
     * // Search from index, and return data directly mapped to entity
     * $index->query()->where('search', 'foo')->all();
     *
     * $index
     *     ->query(function ($data) {
     *         MyEntity::where('id', 'in', )->all();
     *     })
     *     ->where('search', 'foo')
     *     ->all()
     * ;
     * </code>
     *
     * @param callable $processor Process data for return entities. If not provided, will perform simple hydration with indexed fields
     *
     * @return mixed
     */
    public function query(callable $processor = null);

    /**
     * (Re-)Create the index with given data
     * If the index already exists, it should be replaced by the new one (call to drop() is not required)
     *
     * @param iterable $entities Iterable entities list. Can be a walker, or a simple array
     *
     * @return void
     */
    public function create($entities);

    /**
     * Remove the current index
     *
     * @return void
     */
    public function drop();
}
