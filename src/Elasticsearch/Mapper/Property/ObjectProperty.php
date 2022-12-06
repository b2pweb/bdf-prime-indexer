<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Property;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;

/**
 * Store property of type `object`
 */
final class ObjectProperty implements PropertyInterface
{
    /**
     * @var string
     */
    private string $name;

    /**
     * @var class-string
     */
    private string $className;

    /**
     * @var array<string, PropertyInterface>
     */
    private array $properties;

    /**
     * @var PropertyAccessorInterface
     */
    private PropertyAccessorInterface $accessor;

    /**
     * @param string $name The property name
     * @param class-string $className Embedded object type
     * @param array<string, PropertyInterface> $properties Index properties
     * @param PropertyAccessorInterface $accessor Accessor for extract object from container object
     */
    public function __construct(string $name, string $className, array $properties, PropertyAccessorInterface $accessor)
    {
        $this->name = $name;
        $this->className = $className;
        $this->properties = $properties;
        $this->accessor = $accessor;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function declaration(): array
    {
        return [
            'properties' => array_map(
                fn (PropertyInterface $property) => ['type' => $property->type()] + $property->declaration(),
                $this->properties
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return 'object';
    }

    /**
     * {@inheritdoc}
     */
    public function accessor(): PropertyAccessorInterface
    {
        return $this->accessor;
    }

    /**
     * {@inheritdoc}
     */
    public function readFromModel($entity)
    {
        if (!$object = $this->accessor->readFromModel($entity)) {
            return null;
        }

        $normalized = [];

        foreach ($this->properties as $property) {
            $normalized[$property->name()] = $property->readFromModel($object);
        }

        return $normalized;
    }

    /**
     * {@inheritdoc}
     */
    public function writeToModel($entity, $indexedValue)
    {
        $object = $this->accessor->readFromModel($entity);

        if (!$object) {
            $className = $this->className;
            $object = new $className;
            $this->accessor->writeToModel($entity, $object);
        }

        foreach ($this->properties as $property) {
            if (($fieldValue = $indexedValue[$property->name()] ?? null) !== null) {
                $property->writeToModel($object, $fieldValue);
            }
        }
    }
}
