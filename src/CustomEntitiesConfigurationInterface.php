<?php

namespace Bdf\Prime\Indexer;

use Bdf\Prime\Query\Pagination\Walker;

/**
 * Define a custom entities loading method for indexing entities
 */
interface CustomEntitiesConfigurationInterface
{
    /**
     * Get entities to save into the index
     *
     * It's advised to return a Walker instance for lazy load entities,
     * but with total size information, for the progress bar
     *
     * An array can also be returned
     *
     * @return iterable|Walker
     */
    public function entities(): iterable;
}
