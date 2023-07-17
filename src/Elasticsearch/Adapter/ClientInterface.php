<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Adapter;

use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\ElasticsearchExceptionInterface;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\InternalServerException;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\InvalidRequestException;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\NoNodeAvailableException;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\NotFoundException;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Response\Aliases;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\Response\SearchResults;

/**
 * Abstraction layer for elasticsearch client versions
 */
interface ClientInterface
{
    /**
     * Check if an alias exists
     *
     * @param string $name Alias name
     *
     * @return bool true if exists
     *
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     */
    public function hasAlias(string $name): bool;

    /**
     * Get an alias by its name
     *
     * @param string $name Alias name
     *
     * @return Aliases|null The alias, or null if not found
     *
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     */
    public function getAlias(string $name): ?Aliases;

    /**
     * Get all defined alias, indexed by the index name
     *
     * @param string|null $name Filter by alias name
     *
     * @return array<string, Aliases>
     *
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     */
    public function getAllAliases(?string $name = null): array;

    /**
     * Delete aliases for a given index
     *
     * @param string $index Index name
     * @param list<string> $aliases Aliases name
     *
     * @return void
     *
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     */
    public function deleteAliases(string $index, array $aliases): void;

    /**
     * Creates or updates an alias
     *
     * @param string $index Index to alias
     * @param string $name Alias name
     *
     * @return void
     *
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     */
    public function addAlias(string $index, string $name): void;

    /**
     * Check if a document exists
     *
     * @param string $index Index to check
     * @param string $id Document id
     *
     * @return bool true if exists
     *
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     */
    public function exists(string $index, string $id): bool;

    /**
     * Delete a document
     *
     * @param string $index Index to modify
     * @param string $id Document id
     *
     * @return bool true if the document has been deleted. false if the document do not exists.
     *
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     */
    public function delete(string $index, string $id): bool;

    /**
     * Delete documents which match with the given query
     *
     * @param string $index Index to search on and modify
     * @param array $query Elastisearch query. Should follow format of {@see ClientInterface::search()}.
     * @param array $options Extra query options.
     *
     * @return array Delete result
     *
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-delete-by-query.html
     */
    public function deleteByQuery(string $index, array $query, array $options = []): array;

    /**
     * Update a document body
     *
     * @param string $index Index to modify
     * @param string $id Document id
     * @param array{doc: array}|array{script: string|array} $body Fields to update, or script to apply on document
     *
     * @return bool true on success. false if the document do not exist.
     *
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/master/docs-update.html
     */
    public function update(string $index, string $id, array $body): bool;

    /**
     * Update documents which match with the given query
     *
     * @param string $index Index to search on and modify
     * @param array $query Elastisearch query. Should follow format of {@see ClientInterface::search()}.
     * @param array $options Extra query options.
     *
     * @return array Update result
     *
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-update-by-query.html
     */
    public function updateByQuery(string $index, array $query, array $options = []): array;

    /**
     * Perform a search query on an index
     *
     * @param string $index Index to search on
     * @param array $query Elastisearch query
     *
     * @return SearchResults
     *
     * @throws NotFoundException When the index does not exist
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     */
    public function search(string $index, array $query): SearchResults;

    /**
     * Perform a search query on an index and return the matching documents count
     *
     * The result is the same as `$client->search($index, $query)->total()` but does not fetch all documents.
     *
     * @param string $index Index to search on
     * @param array $query Elastisearch query. Same as {@see ClientInterface::search()}
     *
     * @return int Number of documents matching the query
     *
     * @throws NotFoundException When the index does not exist
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     */
    public function count(string $index, array $query): int;

    /**
     * Index a new document, and let elasticsearch to generate its id
     *
     * @param string $index Index to write on
     * @param array $data Document to write
     * @param bool|"wait_for" $refresh Wait synchronously for document to be accessible
     *
     * @return array{
     *     _index: string,
     *     _id: string,
     *     _version: integer,
     *     result: string
     * }
     *
     * @throws NotFoundException When the index does not exist
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     */
    public function index(string $index, array $data, $refresh = false): array;

    /**
     * Create a new document with manual document id
     * This method will fail if a document with same id already exist
     *
     * @param string $index Index to write on
     * @param string $id Document id
     * @param array $data Document to write
     * @param bool|"wait_for" $refresh Wait synchronously for document to be accessible
     *
     * @return array{
     *     _index: string,
     *     _id: string,
     *     _version: integer,
     *     result: string
     * }
     *
     * @throws NotFoundException When the index does not exist
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     */
    public function create(string $index, string $id, array $data, $refresh = false): array;

    /**
     * Create or replace a new document with manual document id
     * This method will replace the old document if already exist
     *
     * @param string $index Index to write on
     * @param string $id Document id
     * @param array $data Document to write
     * @param bool|"wait_for" $refresh Wait synchronously for document to be accessible
     *
     * @return array{
     *     _index: string,
     *     _id: string,
     *     _version: integer,
     *     result: string
     * }
     *
     * @throws NotFoundException When the index does not exist
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     */
    public function replace(string $index, string $id, array $data, $refresh = false): array;

    /**
     * Perform multiple write operation on an index
     *
     * @param array $operations List of write operations
     * @param bool|"wait_for" $refresh Wait synchronously for documents to be accessible
     *
     * @return array
     *
     * @throws NotFoundException When the index does not exist
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     */
    public function bulk(array $operations, $refresh = false): array;

    /**
     * Delete one or more indexes
     * In case of multiple delete, this method will not return false if one of the indexes is not found
     *
     * @param string ...$index Index names
     *
     * @return bool true on success, false if the index does not exist
     *
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     */
    public function deleteIndex(string ...$index): bool;

    /**
     * Refresh (wait for pending write operation) an index
     *
     * @param string $index Index name
     *
     * @return bool true on success
     *
     * @throws NotFoundException When the index does not exist
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     */
    public function refreshIndex(string $index): bool;

    /**
     * Create a new index
     *
     * @param string $index Index name
     * @param array{settings: array, mappings: array} $body Index configuration
     *
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     */
    public function createIndex(string $index, array $body): void;

    /**
     * Check if the requested index exists
     *
     * @param string $index Index name
     *
     * @return bool true if exists
     *
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     */
    public function hasIndex(string $index): bool;

    /**
     * Get all indexes names
     *
     * @return list<string>
     *
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     */
    public function getAllIndexes(): array;

    /**
     * Get all indexes mapping and configurations
     *
     * @return array<string, array> Index definitions indexed by index name
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/master/indices-get-mapping.html
     *
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     */
    public function getAllIndexesMapping(): array;

    /**
     * Get elasticsearch server info
     *
     * @return array
     *
     * @throws InternalServerException When http 500 error occurs
     * @throws InvalidRequestException When request is malformed
     * @throws NoNodeAvailableException If elasticsearch server is down
     * @throws ElasticsearchExceptionInterface When requested cannot be performed
     */
    public function info(): array;

    /**
     * Get the instance of the inner elasticsearch client
     *
     * @return object
     */
    public function getInternalClient();
}
