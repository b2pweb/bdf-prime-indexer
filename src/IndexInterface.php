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
    public function add($entity): void;

    /**
     * Check if the index contains the given entity
     *
     * @param object $entity Entity to check
     *
     * @return boolean True if the entity is indexed
     */
    public function contains($entity): bool;

    /**
     * Remove the entity from the index
     *
     * @param object $entity
     *
     * @return void
     */
    public function remove($entity): void;

    /**
     * Replace the entity data from the index
     *
     * @param object $entity
     *
     * @return void
     */
    public function update($entity): void;

    /**
     * Get the search query
     * If a default scope is declared, depending of the given parameter, it will be applied to the query
     *
     * <code>
     * // Search from index, and return data directly mapped to entity
     * $index->query()->where('search', 'foo')->all();
     *
     * // Same query, but without the default scope
     * $index->query(false)->where('search', 'foo')->all();
     * </code>
     *
     * @param bool $withDefaultScope Enable or not the default scope
     *
     * @return QueryInterface
     */
    public function query(bool $withDefaultScope = true): QueryInterface;

    /**
     * (Re-)Create the index with given data
     * If the index already exists, it should be replaced by the new one (call to drop() is not required)
     *
     * @param iterable $entities Iterable entities list. Can be a walker, or a simple array
     *
     * @return void
     */
    public function create(iterable $entities = []): void;

    /**
     * Remove the current index
     *
     * @return void
     */
    public function drop(): void;

    /**
     * Call a scope
     *
     * @param string $name The scope name
     * @param array $arguments The scope arguments
     *
     * @return QueryInterface
     */
    public function __call(string $name, array $arguments): QueryInterface;
}
