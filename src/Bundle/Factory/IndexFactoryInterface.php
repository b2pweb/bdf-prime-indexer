<?php

namespace Bdf\Prime\Indexer\Bundle\Factory;

use Bdf\Prime\Indexer\IndexInterface;

/**
 * Factory for indexes
 *
 * @template T as object
 */
interface IndexFactoryInterface
{
    /**
     * Get th supported configuration type
     *
     * @return class-string<T>
     */
    public static function type(): string;

    /**
     * Create the index from the configuration object
     *
     * @param T $config The configuration object
     *
     * @return IndexInterface
     */
    public function __invoke($config): IndexInterface;
}
