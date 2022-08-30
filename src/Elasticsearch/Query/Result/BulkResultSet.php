<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Result;

use ArrayAccess;
use BadMethodCallException;
use Bdf\Prime\Connection\Result\ArrayResultSet;
use Bdf\Prime\Connection\Result\ResultSetInterface;

/**
 * ResultSet wrapper for a bulk write result
 *
 * @implements ResultSetInterface<array<string, mixed>>
 */
final class BulkResultSet implements ResultSetInterface, ArrayAccess
{
    /**
     * @var array{took: int, errors: bool, items: list}
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html
     */
    private array $data;

    /**
     * @var ResultSetInterface
     */
    private ResultSetInterface $resultSet;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->resultSet = new ArrayResultSet($data['items']);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->resultSet->current();
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        $this->resultSet->next();
    }

    /**
     * {@inheritdoc}
     */
    public function key(): int
    {
        return $this->resultSet->key();
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return $this->resultSet->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchMode($mode, $options = null)
    {
        $this->resultSet->fetchMode($mode, $options);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asAssociative(): ResultSetInterface
    {
        $this->resultSet->asAssociative();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asList(): ResultSetInterface
    {
        $this->resultSet->asList();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asObject(): ResultSetInterface
    {
        $this->resultSet->asObject();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asClass(string $className, array $constructorArguments = []): ResultSetInterface
    {
        $this->resultSet->asClass($className, $constructorArguments);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asColumn(int $column = 0): ResultSetInterface
    {
        $this->resultSet->asColumn($column);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->resultSet->all();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->resultSet->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->data['items']);
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
}
