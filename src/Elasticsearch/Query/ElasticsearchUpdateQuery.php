<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query;

use Bdf\Prime\Indexer\Elasticsearch\Adapter\ClientInterface;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\ElasticsearchExceptionInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapperInterface;
use Bdf\Prime\Indexer\Elasticsearch\Query\Expression\Script;
use Bdf\Prime\Indexer\Exception\InvalidQueryException;
use Bdf\Prime\Indexer\Exception\QueryExecutionException;
use stdClass;

/**
 * Query for handle update document API
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/7.17/docs-update.html
 */
class ElasticsearchUpdateQuery
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
     * id of document to update
     *
     * @var string|null
     */
    private ?string $id = null;

    /**
     * New document properties
     * If an object is provided, it will be mapped using {@see ElasticsearchMapperInterface}
     *
     * @var array|object|null
     */
    private $document;

    /**
     * Perform an upsert operation if the document do not exists (i.e. insert or update)
     * This value can be an array of properties, or the entity, or true to use the document passed as document parameter
     *
     * @var array|object|true|null
     */
    private $upsert;

    /**
     * Update document using a script
     *
     * @var string|Script|null
     */
    private $script = null;

    /**
     * use script for upsert document
     *
     * @var bool
     */
    private bool $scriptedUpsert = false;

    /**
     * @param ClientInterface $client
     * @param ElasticsearchMapperInterface $mapper
     */
    public function __construct(ClientInterface $client, ElasticsearchMapperInterface $mapper)
    {
        $this->client = $client;
        $this->mapper = $mapper;
    }

    /**
     * Define the index to update
     *
     * <code>
     * $query->from('cities');
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
     * Define id of document to update
     * This value is not required if the id can be resolved from document or upsert document
     *
     * @param string $id
     *
     * @return $this
     */
    public function id(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Define updated document fields
     * If an entity object is given, {@see ElasticsearchMapperInterface} will be used to extra those fields.
     *
     * <code>
     * $query->from('city')
     *     ->document($city)
     *     ->execute()
     * ;
     * </code>
     *
     * @param array|object $document Fields values as array, of entity object
     *
     * @return $this
     */
    public function document($document): self
    {
        $this->document = $document;

        return $this;
    }

    /**
     * Allow to perform an insert operation if the document is not found
     *
     * If `true` is passed as argument, the upserted document will be same as defined with {@see ElasticsearchUpdateQuery::document()}.
     * If an entity object is passed, {@see ElasticsearchMapperInterface} will be used to extract fields.
     *
     * <code>
     * $query->from('city')
     *     ->id('42')
     *     ->document(['foo' => 'bar']) // update "foo" field
     *     ->upsert($city) // document "42" is not found : define fields to insert
     *     ->execute()
     * ;
     * </code>
     *
     * @param array|object|true $upsert Fields (or entity) to insert.
     *
     * @return $this
     */
    public function upsert($upsert = true): self
    {
        $this->upsert = $upsert;

        return $this;
    }

    /**
     * Update document using a script
     *
     * <code>
     * $query
     *     ->from('city')
     *     ->script('ctx._source.counter += 1')
     *     ->id($city->id())
     *     ->execute()
     * ;
     * </code>
     *
     * @param string|Script $script Script code, or object for specify custom language or parameters
     *
     * @return $this
     *
     * @see Script
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/7.17/docs-update.html#update-api-example
     */
    public function script($script): self
    {
        $this->script = $script;

        return $this;
    }

    /**
     * Insert or update document using a script
     *
     * <code>
     * $query
     *     ->from('city')
     *     ->scriptedUpsert(<<<'SCRIPT'
     *         if (ctx.op == 'create') {
     *             ctx._source.count = 1;
     *         } else {
     *             ctx._source.count += 1;
     *         }
     *     SCRIPT
     *     )
     *     ->id($city->id())
     *     ->execute()
     * ;
     * </code>
     *
     * @param string|Script $script Script code, or object for specify custom language or parameters
     *
     * @return $this
     *
     * @see Script
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/7.17/docs-update.html#scripted_upsert
     */
    public function scriptedUpsert($script): self
    {
        $this->scriptedUpsert = true;
        $this->script($script);

        if ($this->upsert === null) {
            $this->upsert = [];
        }

        return $this;
    }

    /**
     * Compile the update query body
     *
     * @return array JSONizable array
     */
    public function compile(): array
    {
        $body = [];

        if ($document = $this->document) {
            $body['doc'] = $this->normalizeDocument($document);
        }

        if ($this->script) {
            $body['script'] = $this->script;
        }

        if ($this->scriptedUpsert) {
            $body['scripted_upsert'] = true;
        }

        if (($upsert = $this->upsert) !== null) {
            if ($upsert === true) {
                $body['doc_as_upsert'] = true;
            } else {
                $body['upsert'] = $this->normalizeDocument($upsert);
            }
        }

        return $body;
    }

    /**
     * Execute the update query
     *
     * @return bool true on success, false if the document cannot be found
     */
    public function execute(): bool
    {
        $id = $this->resolveId();

        if (!$id) {
            throw new InvalidQueryException('Cannot perform update : Document id is missing. Call ElasticsearchUpdateQuery::id() for define the id.');
        }

        try {
            return $this->client->update($this->index, $id, $this->compile());
        } catch (ElasticsearchExceptionInterface $e) {
            throw new QueryExecutionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Try to resolve document id from (in order):
     * - Explicitly defined id
     * - Extracted from field "_id" of document
     * - Extracted from entity of document using mapper
     * - Extracted from field "_id" of upsert
     * - Extracted from entity of upsert using mapper
     *
     * @return string|null resolved id, or null if not found
     */
    private function resolveId(): ?string
    {
        if ($this->id) {
            return $this->id;
        }

        foreach ([$this->document, $this->upsert] as $doc) {
            $id = null;

            if (is_array($doc)) {
                $id = $doc['_id'] ?? null;
            } elseif (is_object($doc)) {
                $id = $this->mapper->id($doc);
            }

            if ($id) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Normalize document to value handled by ES index
     *
     * @param array|object $document
     *
     * @return array|object Normalized document
     */
    private function normalizeDocument($document)
    {
        // Convert entity to array of fields
        if (is_object($document)) {
            $document = $this->mapper->toIndex($document);
        }

        // Remove _id field. It will be set into $this->id
        // and passed as query parameter before execution
        unset($document['_id']);

        // Use empty object instead of empty array because is will be converted to JSON array "[]" instead
        // of JSON object "{}", which is not supported by ES
        return $document ?: new stdClass();
    }
}
