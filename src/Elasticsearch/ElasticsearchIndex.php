<?php

namespace Bdf\Prime\Indexer\Elasticsearch;

use Bdf\Collection\Stream\Streams;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\ClientInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapperInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Property;
use Bdf\Prime\Indexer\Elasticsearch\Query\ElasticsearchCreateQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\ElasticsearchQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\Result\WriteResultSet;
use Bdf\Prime\Indexer\IndexInterface;
use Bdf\Prime\Indexer\QueryInterface;
use Psr\Log\NullLogger;

/**
 * Index implementation for Elasticsearch
 *
 * @final
 */
class ElasticsearchIndex implements IndexInterface
{
    private ClientInterface $client;
    private ElasticsearchMapperInterface $mapper;

    /**
     * ElasticsearchIndex constructor.
     *
     * @param ClientInterface $client
     * @param ElasticsearchMapperInterface $mapper
     */
    public function __construct(ClientInterface $client, ElasticsearchMapperInterface $mapper)
    {
        $this->client = $client;
        $this->mapper = $mapper;
    }

    /**
     * {@inheritdoc}
     */
    public function config(): ElasticsearchIndexConfigurationInterface
    {
        return $this->mapper->configuration();
    }

    /**
     * {@inheritdoc}
     */
    public function add($entity): void
    {
        /** @var WriteResultSet $response */
        $response = $this->creationQuery()->bulk(false)->values($this->mapper->toIndex($entity))->execute();

        $this->mapper->setId($entity, $response->id());
    }

    /**
     * {@inheritdoc}
     */
    public function contains($entity): bool
    {
        if (!$id = $this->mapper->id($entity)) {
            return false;
        }

        return $this->client->exists($this->mapper->configuration()->index(), $id);
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

        $this->client->delete($this->mapper->configuration()->index(), $id);
    }

    /**
     * {@inheritdoc}
     */
    public function update($entity, ?array $attributes = null): void
    {
        $document = $this->mapper->toIndex($entity, $attributes);

        if (empty($document['_id'])) {
            throw new \InvalidArgumentException('Cannot extract id from the entity');
        }

        $id = $document['_id'];
        unset($document['_id']);

        $this->client->update($this->mapper->configuration()->index(), $id, ['doc' => $document]);
    }

    /**
     * {@inheritdoc}
     *
     * @return ElasticsearchQuery
     */
    public function query(bool $withDefaultScope = true): QueryInterface
    {
        $query = (new ElasticsearchQuery($this->client, $this->mapper->scopes()))
            ->from($this->mapper->configuration()->index())
            ->map([$this->mapper, 'fromIndex'])
        ;

        if ($withDefaultScope && isset($this->mapper->scopes()['default'])) {
            $this->mapper->scopes()['default']($query);
        }

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function create(iterable $entities = [], array $options = []): void
    {
        $options += [
            'useAlias' => true,
            'dropPreviousIndexes' => true,
            'chunkSize' => 5000,
            'refresh' => false,
        ];

        if (!isset($options['logger'])) {
            $options['logger'] = new NullLogger();
        }

        $index = $this->mapper->configuration()->index();

        if ($options['useAlias']) {
            $index .= '_' . uniqid();
        }

        try {
            $options['logger']->info('Creating index ' . $index);
            $this->createSchema($index);

            $options['logger']->info('Insert entities into ' . $index);
            $this->insertAll($index, $options['chunkSize'], $entities);

            if ($options['dropPreviousIndexes']) {
                $previousAlias = $this->client->getAlias($this->mapper->configuration()->index());
                $previousIndex = $previousAlias ? $previousAlias->index() : null;
            } else {
                $previousIndex = null;
            }

            if ($options['useAlias']) {
                $options['logger']->info('Adding alias for ' . $index . ' to ' . $this->mapper->configuration()->index());
                $this->client->addAlias($index, $this->mapper->configuration()->index());
            }

            if ($options['dropPreviousIndexes'] && $previousIndex) {
                $options['logger']->info('Removing previous indexes');
                $this->client->deleteIndex($previousIndex);
            }

            if ($options['refresh']) {
                $this->refresh();
            }
        } catch (\Exception $e) {
            $options['logger']->info('Failed creating index ' . $index . ' : ' . $e->getMessage());

            // Delete the index on failure, if alias is used
            if ($options['useAlias'] && $this->client->hasIndex($index)) {
                $this->client->deleteIndex($index);
            }

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function drop(): void
    {
        if ($alias = $this->client->getAlias($this->mapper->configuration()->index())) {
            $alias->delete();
            return;
        }

        $this->client->deleteIndex($this->mapper->configuration()->index());
    }

    /**
     * Get query for perform creations on the elasticsearch index
     *
     * @return ElasticsearchCreateQuery
     */
    public function creationQuery(): ElasticsearchCreateQuery
    {
        return (new ElasticsearchCreateQuery($this->client))
            ->into($this->mapper->configuration()->index())
        ;
    }

    /**
     * Refresh the current index
     * Make all operations performed since the last refresh available for search
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/indices-refresh.html
     */
    public function refresh(): void
    {
        $this->client->refreshIndex($this->mapper->configuration()->index());
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
     * @param string $index The index name to use
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/indices-create-index.html
     */
    private function createSchema(string $index): void
    {
        $body = [
            'settings' => [
                'analysis' => $this->compileAnalysis(),
            ],
            'mappings' => [
                'properties' => $this->compileProperties(),
            ],
        ];

        $this->client->createIndex($index, $body);
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
                            $filter = 'filter_' . $filter;
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
            ->map(function (Property $property) {
                return ['type' => $property->type()] + $property->declaration();
            })
            ->toArray()
        ;
    }

    /**
     * Insert all entities into the index
     *
     * @param string $index The index name to use
     * @param int $chunkSize The insert chunk size
     * @param iterable $entities
     */
    private function insertAll(string $index, int $chunkSize, iterable $entities): void
    {
        $query = $this->creationQuery()->into($index);

        foreach ($entities as $entity) {
            $query->values($this->mapper->toIndex($entity));

            if (count($query) >= $chunkSize) {
                $query->execute();
                $query->clear();
            }
        }

        if (count($query)) {
            $query->execute();
        }
    }
}
