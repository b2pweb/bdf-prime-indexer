<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\Result\BulkResultSet;
use Bdf\Prime\Indexer\Elasticsearch\Query\Result\WriteResultSet;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\CompilerState;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Contract\Query\InsertQueryInterface;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Elasticsearch\Client;
use Elasticsearch\Endpoints\Create;

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
class ElasticsearchCreateQuery implements InsertQueryInterface, \Countable
{
    /** Field name for store the primary key of the document */
    private const PK_FIELD = '_id';

    /**
     * @var Client
     */
    private $client;

    /**
     * Enable bulk insert ?
     *
     * @var bool
     */
    private $bulk = true;

    /**
     * The index name
     *
     * @var string
     */
    private $index;

    /**
     * The requested entry type
     *
     * @var string
     */
    private $type;

    /**
     * Array of values to insert
     *
     * @var array
     */
    private $values = [];

    /**
     * List of columns
     * May be null for disable column check
     *
     * @var string[]
     */
    private $columns = [];

    /**
     * The insert mode
     *
     * @var string
     */
    private $mode = self::MODE_INSERT;

    /**
     * The refresh mode
     *
     * @var bool|string
     */
    private $refresh = false;

    /**
     * Does the current version of elasticsearch library is >= 8.0
     */
    private static ?bool $isV8;

    /**
     * ElasticsearchCreateQuery constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function into($table, $type = null)
    {
        $this->index = $table;
        $this->type = $type ?: $table;

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
     */
    public function execute($columns = null): ResultSetInterface
    {
        if ($this->bulk) {
            return new BulkResultSet($this->client->bulk($this->compileBulk()));
        }

        return new WriteResultSet($this->client->{$this->operation()}($this->compileSimple()));
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
     *
     * Note: It's discouraged to use refresh true on production, due to performance impacts
     *
     * @param boolean|string $mode The refresh mode
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
     */
    public function compile()
    {
        return $this->bulk ? $this->compileBulk() : $this->compileSimple();
    }

    /**
     * Compile for bulk create
     *
     * @return array
     */
    private function compileBulk()
    {
        $body = [];

        foreach ($this->values as $value) {
            $metadata = [
                '_index' => $this->index,
                '_type'  => $this->type,
            ];

            if (isset($value[self::PK_FIELD])) {
                $metadata[self::PK_FIELD] = $value[self::PK_FIELD];
            }

            $body[] = [$this->operation() => $metadata];
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
     */
    private function compileSimple()
    {
        if (empty($this->values)) {
            throw new \InvalidArgumentException('No value to create');
        }

        $query = [
            'index' => $this->index,
            'type'  => $this->type,
            'body'  => $this->compileData($this->values[0]),
        ];

        if (self::isV8()) {
            unset($query['type']);
        }

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
     * Get the operation name from the mode
     *
     * @return string
     * @psalm-return 'index'|'create'
     */
    private function operation()
    {
        if ($this->mode === self::MODE_REPLACE) {
            return 'index';
        }

        // No id given : create cannot be used
        if (!$this->bulk && !isset($this->values[0][self::PK_FIELD])) {
            return 'index';
        }

        return 'create';
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

    /**
     * @inheritDoc
     */
    public function setCustomFilters(array $filters)
    {
        // TODO: Implement setCustomFilters() method.
    }

    /**
     * @inheritDoc
     */
    public function addCustomFilter(string $name, callable $callback)
    {
        // TODO: Implement addCustomFilter() method.
    }

    /**
     * @inheritDoc
     */
    public function getCustomFilters(): array
    {
        // TODO: Implement getCustomFilters() method.
    }

    /**
     * @inheritDoc
     */
    public function statement(string $statement): array
    {
        // TODO: Implement statement() method.
    }

    /**
     * @inheritDoc
     */
    public function addStatement(string $name, $values): void
    {
        // TODO: Implement addStatement() method.
    }

    /**
     * @inheritDoc
     */
    public function buildClause(string $statement, $expression, $operator = null, $value = null, string $type = CompositeExpression::TYPE_AND)
    {
        // TODO: Implement buildClause() method.
    }

    /**
     * @inheritDoc
     */
    public function buildRaw(string $statement, $expression, string $type = CompositeExpression::TYPE_AND)
    {
        // TODO: Implement buildRaw() method.
    }

    /**
     * @inheritDoc
     */
    public function buildNested(string $statement, callable $callback, string $type = CompositeExpression::TYPE_AND)
    {
        // TODO: Implement buildNested() method.
    }

    /**
     * @inheritDoc
     */
    public function addCommand(string $command, $value)
    {
        // TODO: Implement addCommand() method.
    }

    /**
     * @inheritDoc
     */
    public function compiler(): CompilerInterface
    {
        // TODO: Implement compiler() method.
    }

    /**
     * @inheritDoc
     */
    public function setCompiler(CompilerInterface $compiler)
    {
        // TODO: Implement setCompiler() method.
    }

    /**
     * @inheritDoc
     */
    public function connection(): ConnectionInterface
    {
        // TODO: Implement connection() method.
    }

    /**
     * @inheritDoc
     */
    public function on(ConnectionInterface $connection)
    {
        // TODO: Implement on() method.
    }

    /**
     * @inheritDoc
     */
    public function from(string $from, ?string $alias = null)
    {
        // TODO: Implement from() method.
    }

    /**
     * @inheritDoc
     */
    public function preprocessor(): PreprocessorInterface
    {
        // TODO: Implement preprocessor() method.
    }

    /**
     * @inheritDoc
     */
    public function state(): CompilerState
    {
        // TODO: Implement state() method.
    }

    /**
     * @inheritDoc
     */
    public function useQuoteIdentifier(bool $flag = true): void
    {
        // TODO: Implement useQuoteIdentifier() method.
    }

    /**
     * @inheritDoc
     */
    public function isQuoteIdentifier(): bool
    {
        // TODO: Implement isQuoteIdentifier() method.
    }

    /**
     * Does the current version of elasticsearch library is >= 8.0
     */
    private static function isV8(): bool
    {
        if (!isset(self::$isV8)) {
            self::$isV8 = !in_array('type', (new Create())->getParamWhitelist());
        }

        return self::$isV8;
    }
}
