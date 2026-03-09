<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query;

use Bdf\Prime\Indexer\Elasticsearch\Adapter\ClientInterface;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\ElasticsearchExceptionInterface;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Response\SearchResults;
use Bdf\Prime\Indexer\Elasticsearch\ElasticsearchIndex;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapperInterface;
use Bdf\Prime\Indexer\Exception\InvalidQueryException;
use Bdf\Prime\Indexer\Exception\QueryExecutionException;
use Closure;

use function array_keys;
use function array_map;
use function count;

/**
 * Query for perform multiple search queries in a single request
 *
 * @see ElasticsearchIndex::multi() To create a new instance
 */
final class ElasticsearchMultiSearchQuery
{
    private ClientInterface $client;
    private string $index;
    private ?ElasticsearchMapperInterface $mapper;

    /**
     * @var Closure(array):mixed|null
     */
    private ?Closure $transformer = null;

    /**
     * @var array<array-key, ElasticsearchQuery>
     */
    private array $queries = [];

    /**
     * @param ClientInterface $client
     * @param string $index
     * @param ElasticsearchMapperInterface|null $mapper
     */
    public function __construct(ClientInterface $client, string $index, ?ElasticsearchMapperInterface $mapper = null)
    {
        $this->client = $client;
        $this->index = $index;
        $this->mapper = $mapper;

        if ($this->mapper) {
            $this->transformer = Closure::fromCallable([$this->mapper, 'fromIndex']);
        }
    }

    /**
     * Set document transformer for each query result
     * Takes as parameter the "hit" document, and returns the model value
     *
     * <code>
     * $query
     *     ->map(fn ($doc) => new City($doc['_source']))
     *     ->all()
     * ;
     * </code>
     *
     * @param Closure(array):mixed $transformer
     *
     * @return $this
     *
     * @see ElasticsearchQuery::map()
     */
    public function map(Closure $transformer): self
    {
        $this->transformer = $transformer;

        return $this;
    }

    /**
     * Add a new query to the multi search
     *
     * @param ElasticsearchQuery $query The query to add
     * @param int|string|null $key The key to use for the query. This key will be used to identify the query result in the response. If null, an incremental key will be used.
     *
     * @return $this
     */
    public function push(ElasticsearchQuery $query, $key = null): self
    {
        $key ??= count($this->queries);
        $this->queries[$key] = $query;

        return $this;
    }

    /**
     * Create a new query used by the multi search
     *
     * @param int|string|null $key The key to use for the query. This key will be used to identify the query result in the response. If null, an incremental key will be used.
     * @param bool $withDefaultScope If true, the default scope will be applied to the query, if any. This parameter only has effect if the mapper is set.
     *
     * @return ElasticsearchQuery The new query
     */
    public function query($key = null, bool $withDefaultScope = true): ElasticsearchQuery
    {
        $query = (new ElasticsearchQuery($this->client))->from($this->index);

        if ($withDefaultScope && $this->mapper) {
            $scope = $this->mapper->scopes()['default'] ?? null;

            if ($scope) {
                $scope($query);
            }
        }

        $this->push($query, $key);

        return $query;
    }

    /**
     * Execute all queries, and return the results, indexed by the query key
     *
     * @param array<string, mixed> $parameters The parameters to apply on all queries
     *
     * @return array<array-key, SearchResults>
     *
     * @throws QueryExecutionException When query execution failed
     * @throws InvalidQueryException When the query is invalid and cannot be compiled or executed
     */
    public function execute(array $parameters = []): array
    {
        $queries = [];

        foreach ($this->queries as $query) {
            $queries[] = $parameters + $query->compile();
        }

        try {
            $response = $this->client->multiSearch($this->index, $queries);
        } catch (ElasticsearchExceptionInterface $e) {
            throw new QueryExecutionException($e->getMessage(), 0, $e);
        }

        return array_combine(
            array_keys($this->queries),
            $response
        );
    }

    /**
     * Get the count of each query, indexed by the query key
     *
     * @return array<array-key, int>
     *
     * @throws QueryExecutionException When query execution failed
     * @throws InvalidQueryException When the query is invalid and cannot be compiled or executed
     */
    public function count(): array
    {
        $queries = [];

        foreach ($this->queries as $query) {
            $queryBody = $query->compile();
            $queryBody['track_total_hits'] = true; // Ensure that the actual count is returned
            $queryBody['size'] = 0; // Set size to 0 to avoid fetching documents

            $queries[] = $queryBody;
        }

        try {
            $response = $this->client->multiSearch($this->index, $queries);
        } catch (ElasticsearchExceptionInterface $e) {
            throw new QueryExecutionException($e->getMessage(), 0, $e);
        }

        $counts = [];

        foreach ($response as $result) {
            $counts[] = $result->total();
        }

        return array_combine(
            array_keys($this->queries),
            $counts
        );
    }

    /**
     * Get the first result of all queries, indexed by the query key
     * If one of the query has no result, it will be skipped, so its key will not be present in the result
     *
     * Usage:
     * ```php
     * $multiSearch = new ElasticsearchMultiSearchQuery($client, 'index');
     * $multiSearch->query('foo')->match('field', 'foo');
     * $multiSearch->query('bar')->match('field', 'bar');
     * $results = $multiSearch->first(); // ['foo' => ['field' => 'foo', ...], 'bar' => ['field' => 'bar', ...]]
     * ```
     *
     * @return array<array-key, mixed>
     *
     * @throws QueryExecutionException When query execution failed
     * @throws InvalidQueryException When the query is invalid and cannot be compiled or executed
     */
    public function first(): array
    {
        $values = [];

        foreach ($this->execute(['size' => 1]) as $key => $result) {
            $hits = $result->hits();

            if (!$hits) {
                continue;
            }

            $result = $hits[0];

            if ($this->transformer) {
                $result = ($this->transformer)($result);
            }

            $values[$key] = $result;
        }

        return $values;
    }

    /**
     * Get all results of all queries, indexed by the query key
     * If one of the query has no result, an empty array will be returned for this key
     *
     * Usage:
     * ```php
     * $multiSearch = new ElasticsearchMultiSearchQuery($client, 'index');
     * $multiSearch->query('foo')->match('field', 'foo');
     * $multiSearch->query('bar')->match('field', 'bar');
     * $results = $multiSearch->all(); // ['foo' => [...], 'bar' => [...]]
     * ```
     *
     * @return array<array-key, mixed>
     *
     * @throws QueryExecutionException When query execution failed
     * @throws InvalidQueryException When the query is invalid and cannot be compiled or executed
     */
    public function all(): array
    {
        $values = [];

        foreach ($this->execute() as $key => $result) {
            $result = $result->hits();

            if ($this->transformer) {
                $result = array_map($this->transformer, $result);
            }

            $values[$key] = $result;
        }

        return $values;
    }
}
