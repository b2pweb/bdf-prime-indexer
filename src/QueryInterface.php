<?php

namespace Bdf\Prime\Indexer;

use Bdf\Collection\Stream\Streamable;
use Bdf\Collection\Util\OptionalInterface;
use Bdf\Prime\Query\Contract\Whereable;

/**
 * Base type for perform search on index
 */
interface QueryInterface extends Whereable, Streamable
{
    /**
     * Execute the query
     *
     * @return mixed
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
     */
    public function all(): array;

    /**
     * Execute the query and return the first value
     * Same as : `$query->stream()->first()`
     *
     * @return OptionalInterface
     */
    public function first(): OptionalInterface;
}
