<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query;

use Bdf\Collection\Stream\ArrayStream;
use Bdf\Collection\Stream\StreamInterface;
use Bdf\Collection\Util\OptionalInterface;
use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammar;
use Bdf\Prime\Indexer\Elasticsearch\Grammar\ElasticsearchGrammarInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\Compound\BooleanQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\Compound\FunctionScoreQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\Exists;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\Missing;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\WhereFilter;
use Bdf\Prime\Indexer\Elasticsearch\Query\Result\ElasticsearchPaginator;
use Bdf\Prime\Indexer\QueryInterface;
use Bdf\Prime\Query\Contract\Limitable;
use Bdf\Prime\Query\Contract\Orderable;
use Closure;
use Elasticsearch\Client;
use Elasticsearch\Endpoints\AbstractEndpoint;
use Elasticsearch\Endpoints\Search;

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
class ElasticsearchQuery implements QueryInterface, Orderable, Limitable
{
    /**
     * The Elastichsearch client
     *
     * @var Client
     */
    private $client;

    /**
     * The query grammar
     *
     * @var ElasticsearchGrammarInterface
     */
    private $grammar;

    /**
     * List of custom filters, indexed by the filter name
     * Format : [ 'name' => function ($query, $value) { ... } ]
     *
     * @var callable[]
     */
    private $customFilters = [];

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
     * Query to execute
     *
     * @var CompilableExpressionInterface
     */
    private $query;

    /**
     * Order of fields
     * Array with field name as key, and order (asc, desc) as value
     *
     * @var array
     */
    private $order = [];

    /**
     * Offset of the results
     *
     * @var integer|null
     */
    private $from = null;

    /**
     * Maximum items number of the result
     *
     * @var integer|null
     */
    private $size = null;

    /**
     * All query wrappers
     *
     * The first wrapper directly contains the query
     * The last wrapper is the executed query
     *
     * @var WrappingQueryInterface[]
     */
    private $wrappers = [];

    /**
     * The document transformer for get PHP model from the index document
     *
     * @var callable|null
     */
    private $transformer;

    /**
     * Does the current version of elasticsearch library is >= 8.0
     */
    private static ?bool $isV8;


    /**
     * ElasticsearchQuery constructor.
     *
     * @param Client $client
     * @param callable[] $customFilters
     */
    public function __construct(Client $client, array $customFilters = [])
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
     * @param string $type The type name
     *
     * @return $this
     */
    public function from(string $index, string $type)
    {
        $this->index = $index;
        $this->type = $type;

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

        $this->limit((int) $rowCount, (int) $rowCount * ($page - 1));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPage(): int
    {
        if ($this->size === null) {
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
     * @param array|string|Closure $column The expression to compile. Can be name of the column, array expression, or closure
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
     * @param array|string|Closure $column The expression to compile. Can be name of the column, array expression, or closure
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
     * @param array|string|Closure $column The expression to compile. Can be name of the column, array expression, or closure
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
     * @param array|string|Closure $column The expression to compile. Can be name of the column, array expression, or closure
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
    public function execute()
    {
        if (!isset(self::$isV8)) {
            self::$isV8 = !in_array('type', (new Search())->getParamWhitelist());
        }

        $arguments = [
            'index' => $this->index,
            'body' => $this->compile()
        ];

        if (!self::$isV8) {
            $arguments['type'] = $this->type;
        }

        return $this->client->search($arguments);
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
     * Compile the query
     * The query will be used as body of the elasticsearch request
     *
     * @return array JSONizable array
     */
    public function compile()
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
        $stream = new ArrayStream($this->execute()['hits']['hits']);

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
     */
    public function bool(): BooleanQuery
    {
        if (empty($this->query)) {
            return $this->query = new BooleanQuery();
        }

        return $this->query;
    }

    /**
     * Build simple where expression
     *
     * @param array|string|Closure $expression The expression to compile. Can be name of the column, array expression, or closure
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

        if ($expression instanceof Closure) {
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
