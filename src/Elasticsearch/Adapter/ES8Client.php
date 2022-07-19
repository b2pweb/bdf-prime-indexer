<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Adapter;

use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\ElasticsearchExceptionInterface;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\InternalServerException;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\InvalidRequestException;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\NoNodeAvailableException;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\NotFoundException;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\RuntimeException;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Response\Aliases;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Response\SearchResults;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ElasticsearchException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Transport\Exception\NoNodeAvailableException as DriverNoNodeAvailableException;

/**
 * Client adapter for PHP elasticsearch client v8
 */
final class ES8Client implements ClientInterface
{
    private Client $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function getInternalClient(): Client
    {
        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAlias(string $name): bool
    {
        try {
            return $this->client->indices()->existsAlias(['name' => $name])->asBool();
        } catch (ElasticsearchException $e) {
            $this->handleException($e, true);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias(string $name): ?Aliases
    {
        try {
            $response = $this->client->indices()->getAlias(['name' => $name]);

            foreach ($response->asArray() as $index => $data) {
                return new Aliases($this, $index, $data['aliases']);
            }
        } catch (ElasticsearchException $e) {
            $this->handleException($e, true);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllAliases(?string $name = null): array
    {
        $aliases = [];

        try {
            $response = $this->client->indices()->getAlias($name ? ['name' => $name] : []);

            foreach ($response->asArray() as $index => $data) {
                $aliases[$index] = new Aliases($this, $index, $data['aliases']);
            }
        } catch (ElasticsearchException $e) {
            $this->handleException($e);
        }

        return $aliases;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAliases(string $index, array $aliases): void
    {
        try {
            $this->client->indices()->deleteAlias(['index' => $index, 'name' => implode(',', $aliases)]);
        } catch (ElasticsearchException $e) {
            $this->handleException($e, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addAlias(string $index, string $name): void
    {
        try {
            $this->client->indices()->putAlias(['index' => $index, 'name' => $name]);
        } catch (ElasticsearchException $e) {
            $this->handleException($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $index, string $id): bool
    {
        try {
            return $this->client->exists(['index' => $index, 'id' => $id])->asBool();
        } catch (ElasticsearchException $e) {
            $this->handleException($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $index, string $id): bool
    {
        try {
            return $this->client->delete(['index' => $index, 'id' => $id])->asBool();
        } catch (ElasticsearchException $e) {
            $this->handleException($e, true);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $index, string $id, array $body): bool
    {
        try {
            return $this->client->update(['index' => $index, 'id' => $id, 'body' => $body])->asBool();
        } catch (ElasticsearchException $e) {
            $this->handleException($e, true);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function index(string $index, array $data, $refresh = false): array
    {
        try {
            return $this->client->index(['index' => $index, 'body' => $data, 'refresh' => $refresh])->asArray();
        } catch (ElasticsearchException $e) {
            $this->handleException($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $index, string $id, array $data, $refresh = false): array
    {
        try {
            return $this->client->create(['index' => $index, 'id' => $id, 'body' => $data, 'refresh' => $refresh])->asArray();
        } catch (ElasticsearchException $e) {
            $this->handleException($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function replace(string $index, string $id, array $data, $refresh = false): array
    {
        try {
            return $this->client->index(['index' => $index, 'id' => $id, 'body' => $data, 'refresh' => $refresh])->asArray();
        } catch (ElasticsearchException $e) {
            $this->handleException($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $index, array $query): SearchResults
    {
        try {
            $results = $this->client->search(['index' => $index, 'body' => $query])->asArray();
        } catch (ElasticsearchException $e) {
            $this->handleException($e);
        }

        return new SearchResults(
            $results['_scroll_id'] ?? null,
            $results['took'],
            $results['timed_out'],
            $results['_shards'],
            $results['hits']['total']['value'],
            $results['hits']['total']['relation'] === 'eq',
            $results['hits']['max_score'] ?? null,
            $results['hits']['hits'],
            $results
        );
    }

    /**
     * {@inheritdoc}
     */
    public function bulk(array $operations, $refresh = false): array
    {
        try {
            return $this->client->bulk([
                'body' => $operations,
                'refresh' => $refresh
            ])->asArray();
        } catch (ElasticsearchException $e) {
            $this->handleException($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteIndex(string ...$index): bool
    {
        try {
            return $this->client->indices()->delete(['index' => implode(',', $index), 'ignore_unavailable' => count($index) > 1])->asBool();
        } catch (ElasticsearchException $e) {
            $this->handleException($e, true);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function refreshIndex(string $index): bool
    {
        try {
            return $this->client->indices()->refresh(['index' => $index])->asBool();
        } catch (ElasticsearchException $e) {
            $this->handleException($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createIndex(string $index, array $body): void
    {
        try {
            $this->client->indices()->create(['index' => $index, 'body' => $body]);
        } catch (ElasticsearchException $e) {
            $this->handleException($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasIndex(string $index): bool
    {
        try {
            return $this->client->indices()->exists(['index' => $index])->asBool();
        } catch (ElasticsearchException $e) {
            $this->handleException($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAllIndexes(): array
    {
        try {
            return array_keys($this->client->indices()->getMapping()->asArray());
        } catch (ElasticsearchException $e) {
            $this->handleException($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAllIndexesMapping(): array
    {
        try {
            return $this->client->indices()->getMapping()->asArray();
        } catch (ElasticsearchException $e) {
            $this->handleException($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function info(): array
    {
        try {
            return $this->client->info()->asArray();
        } catch (ElasticsearchException $e) {
            $this->handleException($e);
        }
    }

    /**
     * Convert driver exception to driver one
     *
     * @param ElasticsearchException $exception Base driver exception
     * @param bool $ignoreNotFound Ignore http 404 errors
     *
     * @return void|never
     *
     * @throws ElasticsearchExceptionInterface
     */
    private function handleException(ElasticsearchException $exception, bool $ignoreNotFound = false): void
    {
        if ($ignoreNotFound && $exception instanceof ClientResponseException && $exception->getResponse()->getStatusCode() === 404) {
            return;
        }

        switch (true) {
            case $exception instanceof DriverNoNodeAvailableException:
                throw new NoNodeAvailableException($exception->getMessage(), $exception->getCode(), $exception);

            case $exception instanceof ClientResponseException: {
                if ($exception->getResponse()->getStatusCode() === 404) {
                    if ($ignoreNotFound) {
                        return;
                    }

                    throw new NotFoundException($exception->getMessage(), $exception->getCode(), $exception);
                }

                throw new InvalidRequestException($exception->getMessage(), $exception->getCode(), $exception);
            }

            case $exception instanceof ServerResponseException:
                throw new InternalServerException($exception->getMessage(), $exception->getCode(), $exception);

            default:
                throw new RuntimeException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
