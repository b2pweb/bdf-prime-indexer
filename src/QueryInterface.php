<?php

namespace Bdf\Prime\Indexer;

use Bdf\Collection\Stream\Streamable;
use Bdf\Collection\Util\OptionalInterface;
use Bdf\Prime\Indexer\Exception\InvalidQueryException;
use Bdf\Prime\Indexer\Exception\QueryExecutionException;
use Bdf\Prime\Query\Contract\Whereable;

/**
 * Base type for perform search on index
 *
 * @extends Streamable<array-key, mixed>
 */
interface QueryInterface extends Whereable, Streamable
{
    /**
     * Execute the query
     *
     * @return mixed
     *
     * @throws QueryExecutionException When query execution failed
     * @throws InvalidQueryException When the query is invalid and cannot be compiled or executed
     */
    public function execute();

    /**
     * Set document transformer
     * Takes as parameter the "hit" document, and returns the model value
     *
     * <code>
     * $query
     *     ->map(function ($doc) { return new City($doc['_source']); ))
     *     ->stream()
     * ;
     * </code>
     *
     * @param callable $transformer
     *
     * @return $this
     */
    public function map(callable $transformer): QueryInterface;

    /**
     * Execute the query and return all values into an array
     * Same as : `$query->stream()->toArray()`
     *
     * @return array
     *
     * @throws QueryExecutionException When query execution failed
     * @throws InvalidQueryException When the query is invalid and cannot be compiled or executed
     */
    public function all(): array;

    /**
     * Execute the query and return the first value
     * Same as : `$query->stream()->first()`
     *
     * @return OptionalInterface
     *
     * @throws QueryExecutionException When query execution failed
     * @throws InvalidQueryException When the query is invalid and cannot be compiled or executed
     */
    public function first(): OptionalInterface;
}
