<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Result;

use ArrayAccess;
use BadMethodCallException;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use EmptyIterator;

/**
 * ResultSet wrapper for a single document write result
 *
 * @implements ResultSetInterface<array<string, mixed>>
 * @implements ArrayAccess<string, mixed>
 */
final class WriteResultSet extends EmptyIterator implements ResultSetInterface, ArrayAccess
{
    public const RESULT_CREATED = 'created';
    public const RESULT_UPDATED = 'updated';
    public const RESULT_DELETED = 'deleted';
    public const RESULT_NOOP    = 'noop';

    /**
     * @var array{
     *     _index: string,
     *     _id: string,
     *     _version: integer,
     *     result: string,
     *     created: bool,
     *     updated: bool,
     *     deleted: bool,
     *     noop: bool,
     *     ...
     * }
     */
    private array $data;

    /**
     * @param array{ _index: string, _id: string, _version: integer, result: string, ...} $data
     */
    public function __construct(array $data)
    {
        $data[self::RESULT_CREATED] = $data['result'] === self::RESULT_CREATED;
        $data[self::RESULT_UPDATED] = $data['result'] === self::RESULT_UPDATED;
        $data[self::RESULT_DELETED] = $data['result'] === self::RESULT_DELETED;
        $data[self::RESULT_NOOP]    = $data['result'] === self::RESULT_NOOP;

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
        parent::rewind();
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

    /**
     * Check if the query is a creation
     *
     * @return bool
     */
    public function creation(): bool
    {
        return $this->data['result'] === self::RESULT_CREATED;
    }

    /**
     * Check if the query is an update
     *
     * @return bool
     */
    public function update(): bool
    {
        return $this->data['result'] === self::RESULT_UPDATED;
    }

    /**
     * Check if the query is a deletion
     *
     * @return bool
     */
    public function deletion(): bool
    {
        return $this->data['result'] === self::RESULT_DELETED;
    }
}
