<?php

namespace Bdf\Prime\Indexer\Exception;

/**
 * The index configuration is not found
 */
class IndexNotFoundException extends IndexConfigurationException implements PrimeIndexerException
{
    /**
     * @var class-string
     */
    private string $entity;

    /**
     * IndexNotFoundException constructor.
     *
     * @param class-string $entity
     */
    public function __construct(string $entity)
    {
        parent::__construct('The index for entity "'.$entity.'" cannot be found');

        $this->entity = $entity;
    }

    /**
     * Get the requested entity class name
     *
     * @return class-string
     */
    public function entity(): string
    {
        return $this->entity;
    }
}
