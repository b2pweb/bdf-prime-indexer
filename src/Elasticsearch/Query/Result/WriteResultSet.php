<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Result;

use BadMethodCallException;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use EmptyIterator;

/**
 * ResultSet wrapper for a single document write result
 */
final class WriteResultSet extends EmptyIterator implements ResultSetInterface, \ArrayAccess
{
    /**
     * @var array{_index: string, _id: string, _version: integer, result: string}
     */
    private $data;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchMode($mode, $options = null)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asAssociative(): ResultSetInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asList(): ResultSetInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asObject(): ResultSetInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asClass(string $className, array $constructorArguments = []): ResultSetInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asColumn(int $column = 0): ResultSetInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return in_array($this->data['result'], ['created', 'updated', 'deleted']) ? 1 : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isRead(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isWrite(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function hasWrite(): bool
    {
        return $this->count() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        throw new BadMethodCallException();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        throw new BadMethodCallException();
    }

    /**
     * Get the written document id
     *
     * @return string
     */
    public function id(): string
    {
        return $this->data['_id'];
    }
}
