<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Bulk;

use Bdf\Prime\Indexer\Elasticsearch\Adapter\ClientInterface;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\ElasticsearchExceptionInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapperInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\Result\BulkResultSet;
use Bdf\Prime\Indexer\Exception\InvalidQueryException;
use Bdf\Prime\Indexer\Exception\QueryExecutionException;
use Bdf\Prime\Query\Contract\SelfExecutable;
use Countable;
use function count;

/**
 * Query for perform bulk write into an elasticsearch index
 *
 * <code>
 * $query->into('cities')
 *     ->create(new City([
 *        'name' => 'Parthenay',
 *        'population' => 11599,
 *        'country' => 'FR'
 *     ])
 *     ->index(new City([
 *        'name' => 'Paris',
 *        'population' => 2201578,
 *        'country' => 'FR'
 *     ], fn (IndexOperation $op) => $op->id('45'))
 *     ->update(fn (UpdateOperation $op) => $op
 *         ->id('49')
 *         ->script('ctx._source.population += 1500')
 *         ->upsert(new City([
 *             'name' => 'Cavaillon',
 *             'population' => 32000,
 *             'country' => 'FR'
 *         ])
 *     )
 *     ->execute()
 * ;
 * </code>
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html
 */
final class ElasticsearchBulkQuery implements BulkQueryInterface
{
    private ClientInterface $client;
    private ElasticsearchMapperInterface $mapper;

    /**
     * The index name
     *
     * @var string
     */
    private string $index;

    /**
     * Operations to perform
     *
     * @var list<BulkOperationInterface>
     */
    private array $operations = [];

    /**
     * The refresh mode
     *
     * @var true|false|'wait_for'
     */
    private $refresh = false;

    /**
     * @param ClientInterface $client
     * @param ElasticsearchMapperInterface $mapper Mapper used for convert entity to ES document
     */
    public function __construct(ClientInterface $client, ElasticsearchMapperInterface $mapper)
    {
        $this->client = $client;
        $this->mapper = $mapper;
    }

    /**
     * Define the index to write on
     *
     * @param string $index Index name (or alias)
     *
     * @return $this
     */
    public function into(string $index): self
    {
        $this->index = $index;

        return $this;
    }

    /**
     * Add a new write operation to perform
     *
     * @param O $operation Operation to perform
     * @param callable(O):void|null $configurator Closure for configure the operation
     *
     * @return $this
     *
     * @template O as BulkOperationInterface
     */
    public function add(BulkOperationInterface $operation, ?callable $configurator = null): self
    {
        $this->operations[] = $operation;

        if ($configurator) {
            $configurator($operation);
        }

        return $this;
    }

    /**
     * Indexes the specified document. If the document exists, replaces the document and increments the version.
     *
     * <code>
     * $query
     *     ->into('cities')
     *     ->index(new City(...))
     *     ->index($data, fn ($op => $op->id('azerty'))
     *     ->execute()
     * ;
     * </code>
     *
     * @param array|object $document Document to create
     * @param callable(IndexOperation):void|null $configurator Closure for configure the operation
     * @return $this
     *
     * @see IndexOperation
     */
    public function index($document, ?callable $configurator = null): self
    {
        return $this->add(new IndexOperation($document), $configurator);
    }

    /**
     * Indexes the specified document if it does not already exist.
     *
     * <code>
     * $query
     *     ->into('cities')
     *     ->create(new City(...))
     *     ->create($data, fn ($op => $op->id('azerty'))
     *     ->execute()
     * ;
     * </code>
     *
     * @param array|object $document Document to create
     * @param callable(CreateOperation):void|null $configurator Closure for configure the operation
     * @return $this
     *
     * @see CreateOperation
     */
    public function create($document, ?callable $configurator = null): self
    {
        return $this->add(new CreateOperation($document), $configurator);
    }

    /**
     * Removes the specified document from the index.
     *
     * <code>
     * $query
     *     ->into('cities')
     *     ->delete('foo')
     *     ->execute()
     * ;
     * </code>
     *
     * @param string $documentId ID of document to delete
     * @param callable(DeleteOperation):void|null $configurator Closure for configure the operation
     * @return $this
     *
     * @see DeleteOperation
     */
    public function delete(string $documentId, ?callable $configurator = null): self
    {
        return $this->add(new DeleteOperation($documentId), $configurator);
    }

    /**
     * Indexes the specified document if it does not already exist.
     *
     * <code>
     * $query
     *     ->into('cities')
     *     ->update(new City(...))
     *     ->update(fn ($op => $op->id('azerty')->script('ctx._source.population += 1000'))
     *     ->update($city, fn ($op => $op->upsert())
     *     ->execute()
     * ;
     * </code>
     *
     * @param array|object|callable(UpdateOperation):void $document Document to create, or configurator closure
     * @param callable(UpdateOperation):void|null $configurator Closure for configure the operation
     * @return $this
     *
     * @see UpdateOperation
     */
    public function update($document, ?callable $configurator = null): self
    {
        if ($configurator === null && is_callable($document)) {
            $configurator = $document;
            $document = null;
        }

        /** @var array|object|null $document */
        return $this->add(new UpdateOperation($document), $configurator);
    }

    /**
     * {@inheritdoc}
     *
     * @throws QueryExecutionException
     * @throws InvalidQueryException
     */
    public function execute($columns = null): BulkResultSet
    {
        try {
            $query = $this->compile();

            return new BulkResultSet($this->client->bulk($query['body'], $query['refresh'] ?? false));
        } catch (ElasticsearchExceptionInterface $e) {
            throw new QueryExecutionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     *
     * Get the number of pending insert values
     */
    public function count(): int
    {
        return count($this->operations);
    }

    /**
     * Clear the pending insert values
     *
     * @return $this
     */
    public function clear(): self
    {
        $this->operations = [];

        return $this;
    }

    /**
     * Set the index refresh mode after write operation occurs
     *
     * If set to true, the documents will appears immediately on the next search query
     * If set to false, the refresh will be done asynchronously
     * If set to "wait_for" then wait for a refresh to make this operation visible to search
     *
     * Note: It's discouraged to use refresh true on production, due to performance impacts
     *
     * @param boolean|'wait_for' $mode The refresh mode
     *
     * @return $this
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/docs-index_.html#index-refresh
     */
    public function refresh($mode = true): self
    {
        $this->refresh = $mode;

        return $this;
    }

    /**
     * Compile the query
     *
     * @return array
     * @throws InvalidQueryException
     */
    private function compile(): array
    {
        $mapper = $this->mapper;
        $body = [];

        foreach ($this->operations as $operation) {
            $metadata = $operation->metadata($mapper) + ['_index' => $this->index];

            $body[] = [$operation->name() => $metadata];

            if ($value = $operation->value($mapper)) {
                $body[] = $value;
            }
        }

        $query = ['body' => $body];

        if ($this->refresh) {
            $query['refresh'] = $this->refresh;
        }

        return $query;
    }
}
