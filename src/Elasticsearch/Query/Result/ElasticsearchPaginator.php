<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Result;

use Bdf\Prime\Collection\ArrayCollection;
use Bdf\Prime\Indexer\Elasticsearch\Query\ElasticsearchQuery;
use Bdf\Prime\Query\Pagination\AbstractPaginator;
use Bdf\Prime\Query\Pagination\Paginator;
use Bdf\Prime\Query\Pagination\PaginatorInterface;
use IteratorAggregate;

/**
 * Implements paginator for elasticsearch
 */
class ElasticsearchPaginator extends AbstractPaginator implements IteratorAggregate, PaginatorInterface
{
    /**
     * The raw Elasticsearch result
     *
     * @var array
     */
    private $result;

    /**
     * The result transformation function, declared into the query
     *
     * @var callable|null
     *
     * @see ElasticsearchQuery::map()
     */
    private $transformer;


    /**
     * ElasticsearchPaginator constructor.
     *
     * @param ElasticsearchQuery $query
     * @param int|null $maxRows
     * @param int|null $page
     * @param callable|null $transformer
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
        return $this->result['total'];
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->collection;
    }

    /**
     * {@inheritdoc}
     */
    protected function buildSize()
    {
        // No-op
    }

    /**
     * {@inheritdoc}
     */
    protected function loadCollection()
    {
        if ($this->maxRows > -1) {
            $this->query->limitPage($this->page, $this->maxRows);
        }

        $this->result = $this->query->execute()['hits'];
        $this->collection = new ArrayCollection($this->result['hits']);

        if ($this->transformer) {
            $this->collection = $this->collection->map($this->transformer);
        }
    }
}
