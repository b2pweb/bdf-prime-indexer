<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor;

/**
 * Basic property accessor using getter and setter
 */
final class SimplePropertyAccessor implements PropertyAccessorInterface
{
    /**
     * @var string
     */
    private $propertyName;


    /**
     * SimplePropertyAccessor constructor.
     *
     * @param string $propertyName
     */
    public function __construct($propertyName)
    {
        $this->propertyName = $propertyName;
    }

    /**
     * {@inheritdoc}
     */
    public function readFromModel($entity)
    {
        foreach ([$this->propertyName, 'get'.ucfirst($this->propertyName)] as $method) {
            if (method_exists($entity, $method)) {
                return $entity->$method();
            }
        }

        throw new \LogicException('Cannot find getter for property '.$this->propertyName.' on entity '.get_class($entity));
    }

    /**
     * {@inheritdoc}
     */
    public function writeToModel($entity, $indexedValue)
    {
        $entity->{'set'.ucfirst($this->propertyName)}($indexedValue);
    }
}
