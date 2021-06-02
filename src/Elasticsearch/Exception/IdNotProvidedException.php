<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Exception;

use Bdf\Prime\Indexer\Exception\PrimeIndexerException;
use InvalidArgumentException;

/**
 * The id is not provided for a given entity
 */
class IdNotProvidedException extends InvalidArgumentException implements PrimeIndexerException
{
    /**
     * @var class-string
     */
    private $entity;

    /**
     * IdNotProvidedException constructor.
     *
     * @param class-string $entity
     */
    public function __construct(string $entity)
    {
        parent::__construct('Cannot extract id from the entity "'.$entity.'"');

        $this->entity = $entity;
    }

    /**
     * Get the entity class name
     *
     * @return class-string
     */
    public function entity(): string
    {
        return $this->entity;
    }
}
