<?php

namespace Bdf\Prime\Indexer\Exception;

use LogicException;

/**
 * The index configuration is invalid and cannot be used to create an index
 */
class InvalidIndexConfigurationException extends LogicException implements PrimeIndexerException
{
    /**
     * @var class-string
     */
    private $entity;

    /**
     * @var mixed
     */
    private $configuration;

    /**
     * InvalidIndexConfigurationException constructor.
     *
     * @param class-string $entity
     * @param mixed $configuration
     */
    public function __construct(string $entity, $configuration)
    {
        parent::__construct(sprintf('The registered configuration "%s" for entity "%s" is invalid', self::dumpConfig($configuration), $entity));

        $this->entity = $entity;
        $this->configuration = $configuration;
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

    /**
     * Get the resolved configuration
     *
     * @return mixed
     */
    public function configuration()
    {
        return $this->configuration;
    }

    private static function dumpConfig($configuration): string
    {
        if (is_object($configuration)) {
            return get_class($configuration);
        }

        return var_export($configuration, true);
    }
}
