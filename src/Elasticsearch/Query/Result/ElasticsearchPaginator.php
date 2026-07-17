<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Result;

use Bdf\Prime\Collection\ArrayCollection;
use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Response\SearchResults;
use Bdf\Prime\Indexer\Elasticsearch\Query\ElasticsearchQuery;
use Bdf\Prime\PrimeSerializable;
use Bdf\Prime\Query\Pagination\PaginatorInterface;
use IteratorAggregate;
use ReturnTypeWillChange;
use Traversable;

use function count;

/**
 * Implements paginator for elasticsearch
 *
 * @template R as array|object
 *
 * @implements IteratorAggregate<array-key, R>
 * @implements PaginatorInterface<R>
 */
class ElasticsearchPaginator extends PrimeSerializable implements IteratorAggregate, PaginatorInterface
{
    public const DEFAULT_PAGE  = 1;
    public const DEFAULT_LIMIT = 20;

    /**
     * The raw Elasticsearch result
     */
    private SearchResults $result;

    /**
     * The result transformation function, declared into the query
     *
     * @var (callable(mixed):R)|null
     *
     * @see ElasticsearchQuery::map()
     */
    private $transformer;

    /**
     * Current page
     */
    private ?int $page = null;

    /**
     * Number of entities loaded in the collection
     */
    private ?int $maxRows = null;

    private ElasticsearchQuery $query;

    /**
     * @var CollectionInterface<R>
     */
    private CollectionInterface $collection;

    /**
     * ElasticsearchPaginator constructor.
     *
     * @param ElasticsearchQuery $query
     * @param int|null $maxRows
     * @param int|null $page
     * @param (callable(mixed):R)|null $transformer
     */
    public function __construct(ElasticsearchQuery $query, ?int $maxRows = null, ?int $page = null, ?callable $transformer = null)
    {
        $this->query = $query;
        $this->maxRows = $maxRows ?: self::DEFAULT_LIMIT;
        $this->page = $page ?: self::DEFAULT_PAGE;
        $this->transformer = $transformer;

        $this->collection = $this->loadCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function size(): int
    {
        return $this->result->total();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Traversable
    {
        return $this->collection;
    }

    /**
     * {@inheritdoc}
     *
     */
    final public function collection(): CollectionInterface
    {
        return $this->collection;
    }

    /**
     * Get the query
     */
    final public function query(): ElasticsearchQuery
    {
        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function order($attribute = null)
    {
        $orders = $this->query->getOrders();

        if ($attribute === null) {
            return $orders;
        }

        return isset($orders[$attribute]) ? $orders[$attribute] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function limit(): ?int
    {
        return $this->query->getLimit();
    }

    /**
     * {@inheritdoc}
     */
    public function offset(): ?int
    {
        return $this->query->getOffset();
    }

    /**
     * {@inheritdoc}
     */
    public function page()
    {
        return $this->query->getPage();
    }

    /**
     * {@inheritdoc}
     */
    public function pageMaxRows()
    {
        return (int) $this->query->getLimit();
    }

    /**
     * SPL - Countable
     *
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->collection);
    }

    //--------- collection interface

    /**
     * {@inheritdoc}
     */
    public function pushAll(array $items)
    {
        $this->collection->pushAll($items);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function push($item)
    {
        $this->collection->push($item);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function put($key, $item)
    {
        $this->collection->put($key, $item);

        return $this;
    }

    /**
     * SPL - ArrayAccess
     *
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        $this->collection[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->collection->all();
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        return $this->collection->get($key, $default);
    }

    /**
     * {@inheritdoc}
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->collection[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function has($key): bool
    {
        return $this->collection->has($key);
    }

    /**
     * SPL - ArrayAccess
     *
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->collection[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        $this->collection->remove($key);

        return $this;
    }

    /**
     * SPL - ArrayAccess
     *
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        unset($this->collection[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->collection->clear();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): array
    {
        return $this->collection->keys();
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty(): bool
    {
        return $this->collection->isEmpty();
    }

    /**
     * {@inheritdoc}
     *
     * @param callable(R):M $callback The function to run
     * @return static<M> The new collection
     *
     * @template M as array|object
     */
    public function map($callback)
    {
        /** @var static<M> $this */
        $this->collection = $this->collection->map($callback);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function filter($callback = null)
    {
        $this->collection = $this->collection->filter($callback);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy($groupBy, $mode = PaginatorInterface::GROUPBY)
    {
        $this->collection = $this->collection->groupBy($groupBy, $mode);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function contains($element): bool
    {
        return $this->collection->contains($element);
    }

    /**
     * {@inheritdoc}
     */
    public function indexOf($value, $strict = false)
    {
        return $this->collection->indexOf($value, $strict);
    }

    /**
     * {@inheritdoc}
     */
    public function merge($items)
    {
        $this->collection = $this->collection->merge($items);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function sort(?callable $callback = null)
    {
        $this->collection = $this->collection->sort($callback);

        return $this;
    }
    /**
     * @return CollectionInterface<R>
     */
    private function loadCollection(): CollectionInterface
    {
        if ($this->maxRows > -1) {
            $this->query->limitPage($this->page, $this->maxRows);
        }

        $this->result = $this->query->execute();
        $collection = new ArrayCollection($this->result->hits());

        if ($this->transformer) {
            return $collection->map($this->transformer);
        } else {
            return $collection;
        }
    }
}
