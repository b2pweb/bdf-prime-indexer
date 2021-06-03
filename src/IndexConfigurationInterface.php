<?php

namespace Bdf\Prime\Indexer;

/**
 * Base type for define an index configuration
 *
 * @template E as object
 */
interface IndexConfigurationInterface
{
    /**
     * Get the supported entity class name
     *
     * @return class-string<E>
     */
    public function entity(): string;
}
