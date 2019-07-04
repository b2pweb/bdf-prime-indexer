<?php

namespace Bdf\Prime\Indexer;

use Bdf\Collection\Stream\Streamable;
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
    public function map(callable $transformer);
}
