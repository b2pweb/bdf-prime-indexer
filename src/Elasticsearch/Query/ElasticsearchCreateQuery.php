<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query;

use Bdf\Prime\Query\Contract\Query\InsertQueryInterface;
use Elasticsearch\Client;

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
class ElasticsearchCreateQuery implements InsertQueryInterface
{
    /** Field name for store the priamry key of the document */
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
    public function into($index, $type = null)
    {
        $this->index = $index;
        $this->type = $type ?: $index;

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
    public function ignore($flag = true)
    {
        return $this->mode($flag ? self::MODE_IGNORE : self::MODE_INSERT);
    }

    /**
     * {@inheritdoc}
     */
    public function replace($flag = true)
    {
        return $this->mode($flag ? self::MODE_REPLACE : self::MODE_INSERT);
    }

    /**
     * {@inheritdoc}
     */
    public function bulk($flag = true)
    {
        $this->bulk = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($columns = null)
    {
        if ($this->bulk) {
            return $this->client->bulk($this->compileBulk());
        }

        return $this->client->{$this->operation()}($this->compileSimple());
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

        return ['body' => $body];
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

        if (isset($this->values[0][self::PK_FIELD])) {
            $query['id'] = $this->values[0][self::PK_FIELD];
            unset($query['body'][self::PK_FIELD]);
        }

        return $query;
    }

    /**
     * Get the operation name from the mode
     *
     * @return string
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
}