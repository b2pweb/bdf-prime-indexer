<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Result;

use Bdf\Prime\Collection\ArrayCollection;
use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Response\SearchResults;
use Bdf\Prime\Indexer\Elasticsearch\Query\ElasticsearchQuery;
use Bdf\Prime\Query\Pagination\AbstractPaginator;
use Bdf\Prime\Query\Pagination\Paginator;
use Bdf\Prime\Query\Pagination\PaginatorInterface;
use IteratorAggregate;
use Traversable;

/**
 * Implements paginator for elasticsearch
 *
 * @template R as array|object
 * @extends AbstractPaginator<R>
 * @implements IteratorAggregate<array-key, R>
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ElasticsearchPaginator extends AbstractPaginator implements IteratorAggregate, PaginatorInterface
{
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
     * @var ElasticsearchQuery
     * @psalm-suppress NonInvariantDocblockPropertyType
     */
    protected $query;

    /**
     * @var CollectionInterface<R>
     * @psalm-suppress NonInvariantDocblockPropertyType
     */
    protected $collection;

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
        $this->maxRows = $maxRows ?: Paginator::DEFAULT_LIMIT;
        $this->page = $page ?: Paginator::DEFAULT_PAGE;
        $this->transformer = $transformer;

        $this->loadCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function size()
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
     */
    protected function buildSize(): void
    {
        // No-op
    }

    /**
     * {@inheritdoc}
     */
    protected function loadCollection(): void
    {
        if ($this->maxRows > -1) {
            $this->query->limitPage($this->page, $this->maxRows);
        }

        $this->result = $this->query->execute();
        $collection = new ArrayCollection($this->result->hits());

        if ($this->transformer) {
            $this->collection = $collection->map($this->transformer);
        } else {
            /** @psalm-suppress InvalidPropertyAssignmentValue */
            $this->collection = $collection;
        }
    }
}
