<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query;

use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\ClientInterface;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\ElasticsearchExceptionInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\Result\BulkResultSet;
use Bdf\Prime\Indexer\Elasticsearch\Query\Result\WriteResultSet;
use Bdf\Prime\Indexer\Exception\InvalidQueryException;
use Bdf\Prime\Indexer\Exception\QueryExecutionException;
use Bdf\Prime\Query\Contract\BulkWriteBuilderInterface;
use Bdf\Prime\Query\Contract\SelfExecutable;
use Countable;

/**
 * Query for create documents into an elasticsearch index
 *
 * Note: This query is not synchronized. Inserted data cannot be retreived directly.
 *
 * <code>
 * // Perform a bulk insert
 * $query
 *     ->into('cities', 'city')
 *     ->values([
 *         'name' => 'Paris',
 *         'population' => 2201578,
 *         'country' => 'FR'
 *     ])
 *     ->values([
 *         'name' => 'Paris',
 *         'population' => 27022,
 *         'country' => 'US'
 *     ])
 *     ->values([
 *         'name' => 'Cavaillon',
 *         'population' => 26689,
 *         'country' => 'FR'
 *     ])
 *     ->execute()
 * ;
 *
 * // Perform a simple insert
 * $query
 *     ->into('cities', 'city')
 *     ->bulk(false)
 *     ->values([
 *         'name' => 'Paris',
 *         'population' => 2201578,
 *         'country' => 'FR'
 *     ])
 *     ->execute()
 * ;
 * </code>
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/docs-bulk.html
 */
class ElasticsearchCreateQuery implements BulkWriteBuilderInterface, SelfExecutable, Countable
{
    /** Field name for store the primary key of the document */
    private const PK_FIELD = '_id';

    /**
     * @var ClientInterface
     */
    private ClientInterface $client;

    /**
     * Enable bulk insert ?
     *
     * @var bool
     */
    private bool $bulk = true;

    /**
     * The index name
     *
     * @var string
     */
    private string $index;

    /**
     * Array of values to insert
     *
     * @var array
     */
    private array $values = [];

    /**
     * List of columns
     * May be empty for disable column check
     *
     * @var string[]
     */
    private array $columns = [];

    /**
     * The insert mode
     *
     * @var string
     */
    private string $mode = self::MODE_INSERT;

    /**
     * The refresh mode
     *
     * @var true|false|'wait_for'
     */
    private $refresh = false;

    /**
     * ElasticsearchCreateQuery constructor.
     *
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function into($table)
    {
        $this->index = $table;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * An empty column list can be set to disable column filter
     */
    public function columns(array $columns)
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function values(array $data, $replace = false)
    {
        if (!$this->columns) {
            $this->columns = array_keys($data);
        }

        if (!$this->bulk || $replace) {
            $this->values = [$data];
        } else {
            $this->values[] = $data;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function mode($mode)
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @todo handle ignore
     */
    public function ignore(bool $flag = true)
    {
        return $this->mode($flag ? self::MODE_IGNORE : self::MODE_INSERT);
    }

    /**
     * {@inheritdoc}
     */
    public function replace(bool $flag = true)
    {
        return $this->mode($flag ? self::MODE_REPLACE : self::MODE_INSERT);
    }

    /**
     * {@inheritdoc}
     */
    public function bulk(bool $flag = true)
    {
        $this->bulk = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return BulkResultSet|WriteResultSet
     * @throws QueryExecutionException
     * @throws InvalidQueryException
     */
    public function execute($columns = null): ResultSetInterface
    {
        try {
            if ($this->bulk) {
                $query = $this->compileBulk();

                return new BulkResultSet($this->client->bulk($query['body'], $query['refresh'] ?? false));
            }

            $query = $this->compileSimple();

            if (!isset($query['id'])) {
                $result = $this->client->index($query['index'], $query['body'], $query['refresh'] ?? false);
            } elseif ($this->mode === self::MODE_REPLACE) {
                $result = $this->client->replace($query['index'], $query['id'], $query['body'], $query['refresh'] ?? false);
            } else {
                $result = $this->client->create($query['index'], $query['id'], $query['body'], $query['refresh'] ?? false);
            }

            return new WriteResultSet($result);
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
        return count($this->values);
    }

    /**
     * Clear the pending insert values
     *
     * @return $this
     */
    public function clear()
    {
        $this->values = [];

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
    public function refresh($mode = true)
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
    public function compile(): array
    {
        return $this->bulk ? $this->compileBulk() : $this->compileSimple();
    }

    /**
     * Compile for bulk create
     *
     * @return array
     * @throws InvalidQueryException
     */
    private function compileBulk(): array
    {
        $body = [];

        foreach ($this->values as $value) {
            $metadata = ['_index' => $this->index];

            if (isset($value[self::PK_FIELD])) {
                $metadata[self::PK_FIELD] = $value[self::PK_FIELD];
            }

            // Use index mode if the id is not provided
            $operation = $this->mode === self::MODE_REPLACE || !isset($value[self::PK_FIELD]) ? 'index' : 'create';

            $body[] = [$operation => $metadata];
            $body[] = $this->compileData($value);
        }

        $query = ['body' => $body];

        if ($this->refresh) {
            $query['refresh'] = $this->refresh;
        }

        return $query;
    }

    /**
     * Compile for simple create
     *
     * @return array
     * @throws InvalidQueryException
     */
    private function compileSimple(): array
    {
        if (empty($this->values)) {
            throw new InvalidQueryException('No value to create');
        }

        $query = [
            'index' => $this->index,
            'body'  => $this->compileData($this->values[0]),
        ];

        if (isset($this->values[0][self::PK_FIELD])) {
            $query['id'] = $this->values[0][self::PK_FIELD];
            unset($query['body'][self::PK_FIELD]);
        }

        if ($this->refresh) {
            $query['refresh'] = $this->refresh;
        }

        return $query;
    }

    /**
     * Compile the value, corresponding with declared columns
     * Will also remove the "_id" field (Elasticsearch disallow using this field into a document)
     *
     * @param array $value
     *
     * @return array
     */
    private function compileData(array $value): array
    {
        if (!$this->columns) {
            unset($value[self::PK_FIELD]);

            return $value;
        }

        $filtered = [];

        foreach ($this->columns as $column) {
            if ($column !== self::PK_FIELD) {
                $filtered[$column] = $value[$column] ?? null;
            }
        }

        return $filtered;
    }
}
