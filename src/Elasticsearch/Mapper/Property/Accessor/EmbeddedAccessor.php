<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor;

/**
 * Accessor for embedded attribute
 */
final class EmbeddedAccessor implements PropertyAccessorInterface
{
    /**
     * @var array
     */
    private $path;

    /**
     * EmbeddedAccessor constructor.
     *
     * @param array $path
     */
    public function __construct(array $path)
    {
        $this->path = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function readFromModel($entity)
    {
        $value = $entity;

        foreach ($this->path as $name => $class) {
            if (is_int($name)) {
                $name = $class;
            }

            if (($value = $value->$name()) === null) {
                return null;
            }
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function writeToModel($entity, $indexedValue)
    {
        $path = $this->path;
        $last = array_pop($path);

        $owner = $entity;

        foreach ($path as $name => $class) {
            if (is_int($name)) {
                $name = $class;
                $class = null;
            }

            $embedded = $owner->$name();

            if ($embedded === null) {
                if ($class === null) {
                    return;
                }

                $embedded = new $class();
                $owner->{'set' . $name}($embedded);
            }

            $owner = $embedded;
        }

        $owner->{'set' . $last}($indexedValue);
    }
}
