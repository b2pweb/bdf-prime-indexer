<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Property;

use Bdf\Prime\Indexer\Elasticsearch\Grammar\Types;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;

use function array_is_list;
use function array_map;
use function is_array;

/**
 * Store property of type `object`
 * This property handles simple object, or array of objects
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
        return Types::OBJECT;
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
            // Here $object can be null or an empty array
            // So we return the value as is to keep array value if present
            return $object;
        }

        return is_array($object)
            ? array_map([$this, 'normalize'], $object)
            : $this->normalize($object)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function writeToModel($entity, $indexedValue)
    {
        // Consider the property as an array of nested objects if the value is a list (i.e. JSON array)
        // or if the current entity value is an array
        $object = $this->accessor->readFromModel($entity);
        $isListOfObjects = $indexedValue && array_is_list($indexedValue);

        // Convert indexed value to array if the current entity value is an array
        if (!$isListOfObjects && is_array($object)) {
            $indexedValue = $indexedValue ? [$indexedValue] : [];
            $isListOfObjects = true;
        }

        if ($isListOfObjects) {
            $className = $this->className;
            $values = [];

            foreach ($indexedValue as $value) {
                $object = new $className;
                $this->fill($object, (array) $value);
                $values[] = $object;
            }

            $this->accessor->writeToModel($entity, $values);
        } else {
            if (!$object) {
                $className = $this->className;
                $object = new $className;
                $this->accessor->writeToModel($entity, $object);
            }

            $this->fill($object, (array) $indexedValue);
        }
    }

    /**
     * Normalize object to indexed array
     *
     * @param object $object
     * @return array
     */
    private function normalize(object $object): array
    {
        $normalized = [];

        foreach ($this->properties as $property) {
            $normalized[$property->name()] = $property->readFromModel($object);
        }

        return $normalized;
    }

    /**
     * Fill given object with properties values
     *
     * @param object $object
     * @param array<string, mixed> $values
     */
    private function fill(object $object, array $values): void
    {
        foreach ($this->properties as $property) {
            if (($fieldValue = $values[$property->name()] ?? null) !== null) {
                $property->writeToModel($object, $fieldValue);
            }
        }
    }
}
