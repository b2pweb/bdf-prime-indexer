<?php

namespace Bdf\Prime\Indexer;

use Bdf\Prime\Indexer\Exception\ScopeNotFoundException;

/**
 * Store entities for perform complex search
 *
 * @template E as object
 */
interface IndexInterface
{
    /**
     * Get the index configuration
     *
     * @return IndexConfigurationInterface<E>
     */
    public function config();

    /**
     * Store an entity to the index
     *
     * @param E $entity
     *
     * @return void
     */
    public function add($entity): void;

    /**
     * Check if the index contains the given entity
     *
     * @param E $entity Entity to check
     *
     * @return boolean True if the entity is indexed
     */
    public function contains($entity): bool;

    /**
     * Remove the entity from the index
     *
     * @param E $entity
     *
     * @return void
     */
    public function remove($entity): void;

    /**
     * Replace the entity data from the index
     *
     * @param E $entity
     * @param string[]|null $attributes List of attributes to update. If null, all attributes will be updated
     *
     * @return void
     */
    public function update($entity, ?array $attributes = null): void;

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
     * Options :
     * - useAlias (boolean) default: true, write to an alias index
     * - dropPreviousIndexes (boolean) default: true, drop all previous declared indexes
     * - chunkSize (integer) default: 5000, the bulk write size for indexing entities
     *
     * @param iterable<E> $entities Iterable entities list. Can be a walker, or a simple array
     * @param array $options Creation options depends of the indexer
     *
     * @return void
     */
    public function create(iterable $entities = [], array $options = []): void;

    /**
     * Remove the current index
     * Do not fail if the index do not exists
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
     *
     * @throws ScopeNotFoundException When the scope is not declared
     */
    public function __call(string $name, array $arguments): QueryInterface;
}
