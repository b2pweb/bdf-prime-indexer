<?php

namespace Bdf\Prime\Indexer;

use Closure;
use Psr\Log\LoggerInterface;

/**
 * Configuration options for method {@see IndexInterface::create()}
 * This class may be extended by indexer implementation for provide extra parameters
 */
class CreateIndexOptions
{
    /**
     * If true, write to an alias index instead of directly and declare an alias that point to configured index name
     */
    public bool $useAlias = true;

    /**
     * In case of alias usage, drop all previous declared indexes
     */
    public bool $dropPreviousIndexes = true;

    /**
     * How many entities will be indexed by each indexing call ?
     */
    public int $chunkSize = 5000;

    /**
     * Closure called on each entity to index for define value to insert on the bulk write query
     * Takes as first parameter the query object, and on second the entity to index
     *
     * @var Closure(object, object):void|null
     */
    public ?Closure $queryConfigurator = null;

    /**
     * Logger instance to use for log index process
     */
    public ?LoggerInterface $logger = null;

    /**
     * Create option from array or configuration callback
     *
     * @param array|callable(static):void $options
     *
     * @return static
     */
    public static function fromOptions($options): self
    {
        if (is_array($options)) {
            return static::fromArray($options);
        }

        $obj = new static();
        $options($obj);

        return $obj;
    }

    /**
     * Create options object from array of options
     *
     * @param array $options
     *
     * @return static
     */
    public static function fromArray(array $options): self
    {
        $optionsObj = new static();

        if (($useAlias = $options['useAlias'] ?? null) !== null) {
            $optionsObj->useAlias = $useAlias;
        }

        if (($dropPreviousIndexes = $options['dropPreviousIndexes'] ?? null) !== null) {
            $optionsObj->dropPreviousIndexes = $dropPreviousIndexes;
        }

        if ($chunkSize = $options['chunkSize'] ?? null) {
            $optionsObj->chunkSize = $chunkSize;
        }

        if ($queryConfigurator = $options['queryConfigurator'] ?? null) {
            $optionsObj->queryConfigurator = $queryConfigurator;
        }

        if ($logger = $options['logger'] ?? null) {
            $optionsObj->logger = $logger;
        }

        return $optionsObj;
    }
}
