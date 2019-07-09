<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor;

/**
 * Custom accessor using callback
 *
 * <code>
 * $accessor = new CustomAccessor(function ($myEntity, $value) {
 *     if ($value === null) { // Getter
 *         return $myEntity->getValue();
 *     }
 *
 *     // Setter
 *     $myEntity->setValue($value);
 * });
 * </code>
 */
final class CustomAccessor implements PropertyAccessorInterface
{
    /**
     * @var callable
     */
    private $callback;


    /**
     * CustomAccessor constructor.
     *
     * The callback takes as first parameter the entity
     * The second parameter is the value to set, or null in case of getter
     * The function should return the value if the second parameter is null
     *
     * @param callable $callback The accessor function
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function readFromModel($entity)
    {
        return ($this->callback)($entity, null);
    }

    /**
     * {@inheritdoc}
     */
    public function writeToModel($entity, $indexedValue)
    {
        ($this->callback)($entity, $indexedValue);
    }
}
