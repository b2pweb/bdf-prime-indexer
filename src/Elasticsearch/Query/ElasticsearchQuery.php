<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query;

use Bdf\Collection\Stream\ArrayStream;
use Bdf\Collection\Stream\StreamInterface;
use Bdf\Collection\Util\OptionalInterface;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\ClientInterface;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\ElasticsearchExceptionInterface;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Response\SearchResults;
use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammar;
use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammarInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\Compound\BooleanQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\Compound\FunctionScoreQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\Expression\Script;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\Exists;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\Missing;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\WhereFilter;
use Bdf\Prime\Indexer\Elasticsearch\Query\Result\ByQueryWriteResultSet;
use Bdf\Prime\Indexer\Elasticsearch\Query\Result\ElasticsearchPaginator;
use Bdf\Prime\Indexer\Exception\InvalidQueryException;
use Bdf\Prime\Indexer\Exception\QueryExecutionException;
use Bdf\Prime\Indexer\QueryInterface;
use Bdf\Prime\Query\Contract\Limitable;
use Bdf\Prime\Query\Contract\Orderable;
use Closure;
use Countable;

/**
 * Query for perform index search
 *
 * <code>
 * $query
 *     ->from('cities', 'city')
 *     ->where('name', ':like', 'Pari%')
 *     ->stream()
 *     ->map(function ($doc) { return $doc['_source']; })
 *     ->toArray()
 * ;
 * </code>
 */
class ElasticsearchQuery implements QueryInterface, Orderable, Limitable, Countable
{
    /**
     * The Elastichsearch client
     *
     * @var ClientInterface
     */
    private ClientInterface $client;

    /**
     * The query grammar
     *
     * @var ElasticsearchGrammarInterface
     */
    private ElasticsearchGrammarInterface $grammar;

    /**
     * List of custom filters, indexed by the filter name
     * Format : [ 'name' => function ($query, $value) { ... } ]
     *
     * @var callable[]
     */
    private array $customFilters = [];

    /**
     * The index name
     *
     * @var string
     */
    private string $index;

    /**
     * Query to execute
     *
     * @var CompilableExpressionInterface|null
     */
    private ?CompilableExpressionInterface $query = null;

    /**
     * Order of fields
     * Array with field name as key, and order (asc, desc) as value
     *
     * @var array
     */
    private array $order = [];

    /**
     * Offset of the results
     *
     * @var integer|null
     */
    private ?int $from = null;

    /**
     * Maximum items number of the result
     *
     * @var integer|null
     */
    private ?int $size = null;

    /**
     * All query wrappers
     *
     * The first wrapper directly contains the query
     * The last wrapper is the executed query
     *
     * @var WrappingQueryInterface[]
     */
    private array $wrappers = [];

    /**
     * The document transformer for get PHP model from the index document
     *
     * @var callable|null
     */
    private $transformer;


    /**
     * ElasticsearchQuery constructor.
     *
     * @param ClientInterface $client
     * @param callable[] $customFilters
     */
    public function __construct(ClientInterface $client, array $customFilters = [])
    {
        $this->client = $client;
        $this->grammar = new ElasticsearchGrammar();
        $this->customFilters = $customFilters;
    }

    /**
     * Define the index and type name to search
     *
     * <code>
     * $query->from('cities', 'city'); // search into "cities" index, the type "city"
     * </code>
     *
     * @param string $index The index name
     *
     * @return $this
     */
    public function from(string $index)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * Wrap the current query
     *
     * Wrapper will be stacked, and the last wrapper become the executed query
     * The wrappers are used to change the query behavior, like modify score
     *
     * <code>
     * $query->wrap(
     *     (new FunctionScoreQuery())
     *         ->addFunction('field_value_factor', [
     *             'field' => 'population',
     *             'factor' => 1,
     *             'modifier' => 'log1p'
     *         ])
     *         ->scoreMode('multiply')
     * );
     * </code>
     *
     * @param WrappingQueryInterface $wrapper
     *
     * @return $this
     *
     * @see FunctionScoreQuery
     */
    public function wrap(WrappingQueryInterface $wrapper)
    {
        $this->wrappers[] = $wrapper;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function where($column, $operator = null, $value = null)
    {
        return $this->buildWhere($column, $operator, $value, BooleanQuery::COMPOSITE_AND);
    }

    /**
     * {@inheritdoc}
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->buildWhere($column, $operator, $value, BooleanQuery::COMPOSITE_OR);
    }

    /**
     * {@inheritdoc}
     */
    public function whereNull(string $column, string $type = BooleanQuery::COMPOSITE_AND)
    {
        return $this->whereRaw(new Missing($column), $type);
    }

    /**
     * {@inheritdoc}
     */
    public function whereNotNull(string $column, string $type = BooleanQuery::COMPOSITE_AND)
    {
        return $this->whereRaw(new Exists($column), $type);
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereNull(string $column)
    {
        return $this->whereNull($column, BooleanQuery::COMPOSITE_OR);
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereNotNull(string $column)
    {
        return $this->whereNotNull($column, BooleanQuery::COMPOSITE_OR);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|\Bdf\Prime\Query\QueryInterface|\Bdf\Prime\Query\Expression\ExpressionInterface|array|CompilableExpressionInterface $raw
     * @psalm-suppress PossiblyInvalidArgument
     */
    public function whereRaw($raw, string $type = BooleanQuery::COMPOSITE_AND)
    {
        switch ($type) {
            case BooleanQuery::COMPOSITE_AND:
                $this->bool()->and()->filter($raw);
                break;

            case BooleanQuery::COMPOSITE_OR:
                $this->bool()->or()->should($raw);
                break;

            case BooleanQuery::COMPOSITE_FILTER:
                $this->bool()->filter($raw);
                break;

            case BooleanQuery::COMPOSITE_SHOULD:
                $this->bool()->should($raw);
                break;

            case BooleanQuery::COMPOSITE_MUST:
                $this->bool()->must($raw);
                break;

            case BooleanQuery::COMPOSITE_MUST_NOT:
                $this->bool()->mustNot($raw);
                break;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereRaw($raw)
    {
        return $this->whereRaw($raw, BooleanQuery::COMPOSITE_OR);
    }

    /**
     * {@inheritdoc}
     */
    public function nested(callable $callback, string $type = BooleanQuery::COMPOSITE_AND)
    {
        // Save filters, and clear for the nested query
        $query = $this->query;
        $this->query = null;

        $callback($this);

        // Reset the query
        /** @var CompilableExpressionInterface|null $nestedQuery */
        $nestedQuery = $this->query;
        $this->query = $query;

        // Add nested filters
        if ($nestedQuery) {
            $this->whereRaw($nestedQuery, $type);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function order($sort, ?string $order = 'asc')
    {
        if (!is_array($sort)) {
            if (!is_string($sort)) {
                throw new \TypeError('$sort must be of type string or array');
            }

            $this->order = [$sort => $order];
        } else {
            $this->order = $sort;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addOrder($sort, ?string $order = 'asc')
    {
        if (is_array($sort)) {
            $this->order = array_replace($this->order, $sort);
        } else {
            if (!is_string($sort)) {
                throw new \TypeError('$sort must be of type string or array');
            }

            $this->order[$sort] = $order;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrders(): array
    {
        return $this->order;
    }

    /**
     * {@inheritdoc}
     */
    public function limit(?int $limit, ?int $offset = null)
    {
        $this->size = $limit;
        $this->from = $offset;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function limitPage(int $page, int $rowCount = 1)
    {
        $page     = ($page > 0) ? $page : 1;
        $rowCount = ($rowCount > 0) ? $rowCount : 1;

        $this->limit($rowCount, $rowCount * ($page - 1));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPage(): int
    {
        if ($this->size === null || $this->from === null) {
            return 1;
        }

        return (int) ceil($this->from / $this->size) + 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getLimit(): ?int
    {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     */
    public function offset(?int $offset)
    {
        $this->from = $offset;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOffset(): ?int
    {
        return $this->from;
    }

    /**
     * {@inheritdoc}
     */
    public function isLimitQuery(): bool
    {
        return $this->from !== null || $this->size !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPagination(): bool
    {
        return $this->from !== null && $this->size !== null;
    }

    /**
     * Add a "should" filter on the query
     * The query must be a boolean query to works properly
     *
     * Note: composite expressions are combined with "AND".
     *       So should with array of filters means "should match all those filters",
     *       instead of "all those filters should match"
     *
     * <code>
     * $query->should('name', 'Foo');
     * $query->should('name', ':like', 'Foo%');
     * $query->should([
     *     'name' => 'Foo',
     *     'value' => ':like 42%'
     * ]);
     * $query->should(function (ElasticsearchQuery $query) {
     *     $query
     *         ->filter('name', 'Foo')
     *         ->filter('value', ':like', '42%')
     *     ;
     * });
     * $query->should(new Exists('optional-field'));
     * </code>
     *
     * @param string|array<string,mixed>|callable(static):void $column The expression to compile. Can be name of the column, array expression, or closure
     * @param string|mixed $operator The operator (if first argument is column name), or value if value is not given
     * @param mixed $value The comparison value if first argument is the column name
     *
     * @return $this
     *
     * @see BooleanQuery::should()
     */
    public function should($column, $operator = null, $value = null)
    {
        return $this->buildWhere($column, $operator, $value, BooleanQuery::COMPOSITE_SHOULD);
    }

    /**
     * Add a "filter" clause on the query
     * The query must be a boolean query to works properly
     *
     * <code>
     * $query->filter('name', 'Foo');
     * $query->filter('name', ':like', 'Foo%');
     * $query->filter([
     *     'name' => 'Foo',
     *     'value' => ':like 42%'
     * ]);
     * $query->filter(function (ElasticsearchQuery $query) {
     *     $query
     *         ->where('name', 'Foo')
     *         ->orWhere('value', ':like', '42%')
     *     ;
     * });
     * </code>
     *
     * @param string|array<string,mixed>|callable(static):void $column The expression to compile. Can be name of the column, array expression, or closure
     * @param string|mixed $operator The operator (if first argument is column name), or value if value is not given
     * @param mixed $value The comparison value if first argument is the column name
     *
     * @return $this
     *
     * @see BooleanQuery::filter()
     */
    public function filter($column, $operator = null, $value = null)
    {
        return $this->buildWhere($column, $operator, $value, BooleanQuery::COMPOSITE_FILTER);
    }

    /**
     * Add a "must" clause on the query
     * The query must be a boolean query to works properly
     *
     * Note: Works as filter(), but with the modification of the document's score
     *
     * <code>
     * $query->must('name', 'Foo');
     * $query->must('name', ':like', 'Foo%');
     * $query->must([
     *     'name' => 'Foo',
     *     'value' => ':like 42%'
     * ]);
     * $query->must(function (ElasticsearchQuery $query) {
     *     $query
     *         ->where('name', 'Foo')
     *         ->orWhere('value', ':like', '42%')
     *     ;
     * });
     * </code>
     *
     * @param string|array<string,mixed>|callable(static):void $column The expression to compile. Can be name of the column, array expression, or closure
     * @param string|mixed $operator The operator (if first argument is column name), or value if value is not given
     * @param mixed $value The comparison value if first argument is the column name
     *
     * @return $this
     *
     * @see BooleanQuery::must()
     */
    public function must($column, $operator = null, $value = null)
    {
        return $this->buildWhere($column, $operator, $value, BooleanQuery::COMPOSITE_MUST);
    }

    /**
     * Add a "must_not" clause on the query
     * The query must be a boolean query to works properly
     *
     * <code>
     * $query->must('name', 'Foo');
     * $query->must('name', ':like', 'Foo%');
     * $query->must([
     *     'name' => 'Foo',
     *     'value' => ':like 42%'
     * ]);
     * $query->must(function (ElasticsearchQuery $query) {
     *     $query
     *         ->where('name', 'Foo')
     *         ->orWhere('value', ':like', '42%')
     *     ;
     * });
     * </code>
     *
     * @param string|array<string,mixed>|callable(static):void $column The expression to compile. Can be name of the column, array expression, or closure
     * @param string|mixed $operator The operator (if first argument is column name), or value if value is not given
     * @param mixed $value The comparison value if first argument is the column name
     *
     * @return $this
     *
     * @see BooleanQuery::mustNot()
     */
    public function mustNot($column, $operator = null, $value = null)
    {
        return $this->buildWhere($column, $operator, $value, BooleanQuery::COMPOSITE_MUST_NOT);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): SearchResults
    {
        try {
            return $this->client->search($this->index, $this->compile());
        } catch (ElasticsearchExceptionInterface $e) {
            throw new QueryExecutionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        try {
            return $this->client->count($this->index, $this->compile());
        } catch (ElasticsearchExceptionInterface $e) {
            throw new QueryExecutionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->stream()->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function first(): OptionalInterface
    {
        return $this->stream()->first();
    }

    /**
     * Get a paginator for the query
     *
     * @param int|null $maxRows The number of rows per page
     * @param int|null $page The page number
     *
     * @return ElasticsearchPaginator
     */
    public function paginate(?int $maxRows = null, ?int $page = null): ElasticsearchPaginator
    {
        return new ElasticsearchPaginator($this, $maxRows, $page, $this->transformer);
    }

    /**
     * Execute an update by query operation
     *
     * <code>
     * $query->from('cities')
     *     ->where('zipcode', '84300')
     *     ->update('ctx._source.population += 1000')
     * ;
     * </code>
     *
     * @param Script|string|null $script Script called on all matched document for perform update of source.
     *
     * @return ByQueryWriteResultSet
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-update-by-query.html
     */
    public function update($script = null): ByQueryWriteResultSet
    {
        $query = $this->compile();

        if ($script) {
            $query['script'] = $script;
        }

        try {
            return new ByQueryWriteResultSet($this->client->updateByQuery($this->index, $query));
        } catch (ElasticsearchExceptionInterface $e) {
            throw new QueryExecutionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete documents matching with current query
     *
     * <code>
     * $query->from('cities')
     *     ->where('zipcode', '84300')
     *     ->delete()
     * ;
     * </code>
     *
     * @return ByQueryWriteResultSet
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-delete-by-query.html
     */
    public function delete(): ByQueryWriteResultSet
    {
        try {
            return new ByQueryWriteResultSet($this->client->deleteByQuery($this->index, $this->compile()));
        } catch (ElasticsearchExceptionInterface $e) {
            throw new QueryExecutionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Compile the query
     * The query will be used as body of the elasticsearch request
     *
     * @return array JSONizable array
     */
    public function compile(): array
    {
        $query = $this->query;

        foreach ($this->wrappers as $wrapper) {
            $query = $wrapper->wrap($query);
        }

        $body = [];

        if ($query) {
            $body['query'] = $query->compile($this->grammar);
        }

        if ($this->order) {
            $body['sort'] = $this->compileSort();
        }

        if ($this->size !== null) {
            $body['size'] = $this->size;
        }

        if ($this->from !== null) {
            $body['from'] = $this->from;
        }

        return $body;
    }

    /**
     * {@inheritdoc}
     */
    public function stream(): StreamInterface
    {
        $stream = new ArrayStream($this->execute()->hits());

        return $this->transformer ? $stream->map($this->transformer) : $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $transformer): QueryInterface
    {
        $this->transformer = $transformer;

        return $this;
    }

    /**
     * Get "bool" filter query
     *
     * @return BooleanQuery
     *
     * @psalm-assert BooleanQuery $this->query
     * @throws InvalidQueryException If the query is not a boolean query
     */
    public function bool(): BooleanQuery
    {
        if (empty($this->query)) {
            return $this->query = new BooleanQuery();
        }

        if (!$this->query instanceof BooleanQuery) {
            throw new InvalidQueryException('This query is not configured as boolean query.');
        }

        return $this->query;
    }

    /**
     * Build simple where expression
     *
     * @param string|array<string,mixed>|callable(static):void $expression The expression to compile. Can be name of the column, array expression, or closure
     * @param string|mixed $operator The operator (if first argument is column name), or value if value is not given
     * @param mixed $value The comparison value if first argument is the column name
     * @param string $type The composite expression type (and/or)
     *
     * @return $this
     */
    private function buildWhere($expression, $operator, $value, $type)
    {
        if ($expression instanceof CompilableExpressionInterface) {
            return $this->whereRaw($expression, $type);
        }

        if (!is_string($expression) && is_callable($expression)) {
            return $this->nested($expression, $type);
        }

        if (is_array($expression)) {
            return $this->buildArrayExpression($expression, $type);
        }

        //if no value. Check if operator is a value. Otherwise we assume it is a 'is null' request
        if ($value === null && (!is_string($operator) || !isset($this->operators[$operator]))) {
            $value = $operator;
            $operator = '=';
        }

        /** @var string $expression */
        if (isset($this->customFilters[$expression])) {
            // Custom filter
            $this->customFilters[$expression]($this, $value);
        } else {
            $this->whereRaw(new WhereFilter($expression, $operator, $value), $type);
        }

        return $this;
    }

    /**
     * Build array expression
     *
     * @param array $expression
     * @param string $type
     *
     * @return $this
     */
    private function buildArrayExpression(array $expression, $type = BooleanQuery::COMPOSITE_AND)
    {
        //nested expression
        $bool = new BooleanQuery();

        foreach ($expression as $key => $value) {
            if (isset($this->customFilters[$key])) {
                // Custom filter
                $lastFilters = $this->query;
                $this->query = $bool;

                $this->customFilters[$key]($this, $value);

                $this->query = $lastFilters;
            } elseif (is_int($key)) {
                // Raw value
                $bool->filter($value);
            } else {
                // Column with operator
                $key  = explode(' ', trim($key), 2);

                $bool->filter(new WhereFilter($key[0], $key[1] ?? '=', $value));
            }
        }

        if (!$bool->empty()) {
            $this->whereRaw($bool, $type);
        }

        return $this;
    }

    /**
     * Compile the sort clause
     *
     * @return array
     */
    private function compileSort(): array
    {
        $sort = [];

        foreach ($this->order as $field => $order) {
            $sort[] = [$field => $order];
        }

        return $sort;
    }
}
