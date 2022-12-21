<?php

namespace Bdf\Prime\Indexer;

use Bdf\Prime\Indexer\Exception\InvalidQueryException;
use Bdf\Prime\Indexer\Exception\QueryExecutionException;

/**
 * Store entities for perform complex search
 */
interface IndexInterface
{
    /**
     * Get the index configuration
     *
     * @return object
     */
    public function config();

    /**
     * Store an entity to the index
     *
     * @param object $entity
     *
     * @return void
     *
     * @throws QueryExecutionException When query execution failed
     * @throws InvalidQueryException When the query is invalid and cannot be compiled or executed
     */
    public function add($entity): void;

    /**
     * Check if the index contains the given entity
     *
     * @param object $entity Entity to check
     *
     * @return boolean True if the entity is indexed
     *
     * @throws QueryExecutionException When query execution failed
     * @throws InvalidQueryException When the query is invalid and cannot be compiled or executed
     */
    public function contains($entity): bool;

    /**
     * Remove the entity from the index
     *
     * @param object $entity
     *
     * @return void
     *
     * @throws QueryExecutionException When query execution failed
     * @throws InvalidQueryException When the query is invalid and cannot be compiled or executed
     */
    public function remove($entity): void;

    /**
     * Replace the entity data from the index
     *
     * @param object $entity
     * @param string[]|null $attributes List of attributes to update. If null, all attributes will be updated
     *
     * @return void
     *
     * @throws QueryExecutionException When query execution failed
     * @throws InvalidQueryException When the query is invalid and cannot be compiled or executed
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
     *
     * @throws InvalidQueryException When the query is invalid
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
     * - logger (LoggerInterface)
     *
     * @param iterable $entities Iterable entities list. Can be a walker, or a simple array.
     * @param array|callable(CreateIndexOptions):void $options Configure creation options.
     *
     * @return void
     *
     * @throws QueryExecutionException When query execution failed
     * @throws InvalidQueryException When the query is invalid and cannot be compiled or executed
     *
     * @see CreateIndexOptions
     */
    public function create(iterable $entities = [], $options = []): void;

    /**
     * Remove the current index
     * Do not fail if the index do not exists
     *
     * @return void
     *
     * @throws QueryExecutionException When query execution failed
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
     * @throws InvalidQueryException If the score does not exist, or parameters are invalid
     */
    public function __call(string $name, array $arguments): QueryInterface;
}
