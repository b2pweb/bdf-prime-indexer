<?php

namespace Bdf\Prime\Indexer;

use Bdf\Prime\Query\Contract\Whereable;

/**
 * Perform search on index
 */
interface QueryInterface extends Whereable
{
    /**
     * Execute the query
     *
     * @return mixed
     */
    public function execute();
}
