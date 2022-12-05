<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Bulk;

use Bdf\Prime\Query\Contract\SelfExecutable;
use Countable;

interface BulkQueryInterface extends SelfExecutable, Countable
{
    /**
     * Define the index to write on
     *
     * @param string $index Index name (or alias)
     *
     * @return $this
     */
    public function into(string $index): self;

    /**
     * {@inheritdoc}
     *
     * Get the number of pending insert values
     */
    public function count(): int;

    /**
     * Clear the pending insert values
     *
     * @return $this
     */
    public function clear(): self;
}
