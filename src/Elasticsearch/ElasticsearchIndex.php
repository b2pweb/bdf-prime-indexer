<?php

namespace Bdf\Prime\Indexer\Elasticsearch;

use Bdf\Collection\Stream\Streams;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapperInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Property;
use Bdf\Prime\Indexer\Elasticsearch\Query\ElasticsearchCreateQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\ElasticsearchQuery;
use Bdf\Prime\Indexer\IndexInterface;
use Bdf\Prime\Indexer\QueryInterface;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;

/**
 * Index implementation for Elasticsearch
 */
class ElasticsearchIndex implements IndexInterface
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var ElasticsearchMapperInterface
     */
    private $mapper;


    /**
     * ElasticsearchIndex constructor.
     *
     * @param Client $client
     * @param ElasticsearchMapperInterface $mapper
     */
    public function __construct(Client $client, ElasticsearchMapperInterface $mapper)
    {
        $this->client = $client;
        $this->mapper = $mapper;
    }

    /**
     * {@inheritdoc}
     */
    public function add($entity): void
    {
        $response = $this->creationQuery()->bulk(false)->values($this->mapper->toIndex($entity))->execute();

        $this->mapper->setId($entity, $response['_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($entity): bool
    {
        if (!$id = $this->mapper->id($entity)) {
            return false;
        }

        return $this->client->exists([
            'index' => $this->mapper->configuration()->index(),
            'type' => $this->mapper->configuration()->type(),
            'id' => $id,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($entity): void
    {
        $id = $this->mapper->id($entity);

        if (empty($id)) {
            throw new \InvalidArgumentException('Cannot extract id from the entity');
        }

        $this->client->delete([
            'index' => $this->mapper->configuration()->index(),
            'type' => $this->mapper->configuration()->type(),
            'id' => $id,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function update($entity): void
    {
        $document = $this->mapper->toIndex($entity);

        if (empty($document['_id'])) {
            throw new \InvalidArgumentException('Cannot extract id from the entity');
        }

        $id = $document['_id'];
        unset($document['_id']);

        $this->client->update([
            'index' => $this->mapper->configuration()->index(),
            'type' => $this->mapper->configuration()->type(),
            'id' => $id,
            'body' => [
                'doc' => $document,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     *
     * @return ElasticsearchQuery
     */
    public function query(bool $withDefaultScope = true): QueryInterface
    {
        $query = (new ElasticsearchQuery($this->client, $this->mapper->scopes()))
            ->from($this->mapper->configuration()->index(), $this->mapper->configuration()->type())
            ->map([$this->mapper, 'fromIndex'])
        ;

        if ($withDefaultScope && isset($this->mapper->scopes()['default'])) {
            $this->mapper->scopes()['default']($query);
        }

        return $query;
    }

    /**
     * {@inheritdoc}
     *
     * @todo set id ?
     * @todo Index alias
     */
    public function create(iterable $entities = []): void
    {
        $this->createSchema();
        $this->insertAll($entities);
    }

    /**
     * {@inheritdoc}
     */
    public function drop(): void
    {
        try {
            $this->client->indices()->delete(['index' => $this->mapper->configuration()->index()]);
        } catch (Missing404Exception $e) {
            // Index not found : do not raise the exception
        }
    }

    /**
     * Get query for perform creations on the elasticsearch index
     *
     * @return ElasticsearchCreateQuery
     */
    public function creationQuery(): ElasticsearchCreateQuery
    {
        return (new ElasticsearchCreateQuery($this->client))
            ->into($this->mapper->configuration()->index(), $this->mapper->configuration()->type())
        ;
    }

    /**
     * Call a scope
     *
     * @param string $name The scope name
     * @param array $arguments The scope arguments
     *
     * @return ElasticsearchQuery
     */
    public function __call(string $name, array $arguments): QueryInterface
    {
        if (!isset($this->mapper->scopes()[$name])) {
            throw new \BadMethodCallException('The scope '.$name.' cannot be found');
        }

        $query = $this->query();
        $this->mapper->scopes()[$name]($query, ...$arguments);

        return $query;
    }

    /**
     * Create the index schema
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/indices-create-index.html
     */
    private function createSchema()
    {
        $this->client->indices()->create([
            'index' => $this->mapper->configuration()->index(),
            'body' => [
                'settings' => [
                    'analysis' => $this->compileAnalysis(),
                ],
                'mappings' => [
                    $this->mapper->configuration()->type() => [
                        'properties' => $this->compileProperties(),
                    ],
                ],
            ],
        ]);
    }

    /**
     * Compile the "analysis" settings
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/analysis.html
     */
    private function compileAnalysis(): array
    {
        $analysis = [
            'analyzer' => [],
            'tokenizer' => [],
            'filter' => [],
        ];

        foreach ($this->mapper->analyzers() as $name => $analyzer) {
            $declaration = $analyzer->declaration();

            if ($tokenizer = $analyzer->tokenizer()) {
                if (is_string($tokenizer)) {
                    $declaration['tokenizer'] = $tokenizer;
                } else {
                    $declaration['tokenizer'] = $name;
                    $analysis['tokenizer'][$name] = $tokenizer;
                }
            }

            if ($filters = $analyzer->filters()) {
                $declaration['filter'] = [];

                foreach ($filters as $filter => $filterDeclaration) {
                    if (is_string($filterDeclaration)) {
                        $declaration['filter'][] = $filterDeclaration;
                    } else {
                        if (is_int($filter)) {
                            $filter = 'filter_'.$filter;
                        }

                        $declaration['filter'][] = $filter;
                        $analysis['filter'][$filter] = $filterDeclaration;
                    }
                }
            }

            $analysis['analyzer'][$name] = $declaration;
        }

        return array_filter($analysis);
    }

    /**
     * Compile the type properties
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/properties.html
     */
    private function compileProperties(): array
    {
        return Streams::wrap($this->mapper->properties())
            ->map(function (Property $property) { return ['type' => $property->type()] + $property->declaration(); })
            ->toArray()
        ;
    }

    /**
     * Insert all entities into the index
     *
     * @param iterable $entities
     */
    private function insertAll(iterable $entities): void
    {
        $query = $this->creationQuery();

        // @todo check empty
        // @todo chunk
        foreach ($entities as $entity) {
            $query->values($this->mapper->toIndex($entity));
        }

        $query->execute();
    }
}
