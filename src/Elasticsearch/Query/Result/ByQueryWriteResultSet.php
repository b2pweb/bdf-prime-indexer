<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Result;

use ArrayAccess;
use BadMethodCallException;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use EmptyIterator;

/**
 * Result wrapper for write operation perform on documents matching with a query
 *
 * Unlike {@see WriteResultSet} the write operation can be performed on multiple documents, so count can be higher than 1.
 *
 * @implements ArrayAccess<string, mixed>
 * @implements ResultSetInterface<array<string, mixed>>
 */
final class ByQueryWriteResultSet extends EmptyIterator implements ResultSetInterface, ArrayAccess
{
    /**
     * @var array{
     *     took: int,
     *     timed_out: bool,
     *     total: int,
     *     updated?: int,
     *     deleted?: int,
     *     batches: int,
     *     version_conflicts: int,
     *     noops: int,
     *     retries: array,
     *     throttled_millis: int,
     *     failures: array,
     * }
     */
    private array $data;

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
    public function all(): array
    {
        return [];
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
    public function count(): int
    {
        return $this->updated() + $this->deleted();
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
     * Number of updated document
     */
    public function updated(): int
    {
        return $this->data['updated'] ?? 0;
    }

    /**
     * Number of removed document
     */
    public function deleted(): int
    {
        return $this->data['deleted'] ?? 0;
    }

    /**
     * Number of ignored documents
     */
    public function noops(): int
    {
        return $this->data['noops'] ?? 0;
    }

    /**
     * Total number of matching documents
     * This value may differ with {@see ResultSetInterface::count()} if some document are skipped, which results to "noops" counter increment
     */
    public function total(): int
    {
        return $this->data['total'];
    }
}
