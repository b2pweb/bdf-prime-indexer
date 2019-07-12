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
use Psr\Log\NullLogger;

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
    public function config()
    {
        return $this->mapper->configuration();
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

        try {
            $this->client->delete([
                'index' => $this->mapper->configuration()->index(),
                'type'  => $this->mapper->configuration()->type(),
                'id'    => $id,
            ]);
        } catch (Missing404Exception $e) {
            // Ignore deleting not found entities
        }
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
            $index .= '_'.uniqid();
        }

        try {
            $options['logger']->info('Creating index '.$index);
            $this->createSchema($index);

            $options['logger']->info('Insert entities into '.$index);
            $this->insertAll($index, $options['chunkSize'], $entities);

            if ($options['useAlias']) {
                $options['logger']->info('Adding alias for '.$index.' to '.$this->mapper->configuration()->index());
                $this->client->indices()->putAlias([
                    'index' => $index,
                    'name'  => $this->mapper->configuration()->index()
                ]);
            }

            if ($options['dropPreviousIndexes']) {
                $options['logger']->info('Removing previous indexes');
                $this->dropPreviousIndexes($index);
            }

            if ($options['refresh']) {
                $this->refresh();
            }
        } catch (\Exception $e) {
            $options['logger']->info('Failed creating index '.$index.' : '.$e->getMessage());

            // Delete the index on failure, if alias is used
            if ($options['useAlias'] && $this->client->indices()->exists(['index' => $index])) {
                $this->client->indices()->delete(['index' => $index]);
            }

            throw $e;
        }
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
     * Refresh the current index
     * Make all operations performed since the last refresh available for search
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/indices-refresh.html
     */
    public function refresh(): void
    {
        $this->client->indices()->refresh(['index' => $this->mapper->configuration()->index()]);
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
    private function createSchema(string $index)
    {
        $this->client->indices()->create([
            'index' => $index,
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
     * @param string $index The index name to use
     * @param int $chunkSize The insert chunk size
     * @param iterable $entities
     */
    private function insertAll(string $index, int $chunkSize, iterable $entities): void
    {
        $query = $this
            ->creationQuery()
            ->into($index, $this->mapper->configuration()->type())
        ;

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

    /**
     * Drop all previous indexes, excluding the current one
     *
     * @param string $index The current index (to keep)
     */
    private function dropPreviousIndexes(string $index): void
    {
        $alias = $this->mapper->configuration()->index();

        if (!$this->client->indices()->existsAlias(['name' => $alias])) {
            return;
        }

        // Get indexes, and remove the current
        $indexes = $this->client->indices()->getAlias(['name' => $alias]);
        unset($indexes[$index]);

        if (!empty($indexes)) {
            $this->client->indices()->delete(['index' => implode(',', array_keys($indexes))]);
        }
    }
}
