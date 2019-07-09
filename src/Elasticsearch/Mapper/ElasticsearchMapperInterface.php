<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\AnalyzerInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Property;

/**
 * Mapper for Elasticsearch index
 */
interface ElasticsearchMapperInterface
{
    /**
     * Get the index configuration for the mapper
     *
     * @return ElasticsearchIndexConfigurationInterface
     */
    public function configuration(): ElasticsearchIndexConfigurationInterface;

    /**
     * Get the list of indexed properties of the entity
     * The properties are indexed by the index field name
     *
     * @return Property[]
     */
    public function properties(): array;

    /**
     * Get list of declared analyzers
     * The analyzers are indexed by name
     *
     * @return AnalyzerInterface[]
     */
    public function analyzers(): array;

    /**
     * Get list of scopes indexed by name
     * If the scope 'default' is set, it should be applied to all created queries
     *
     * The scope takes as first parameter the ElasticsearchQuery, and other parameters are the user parameters
     * The scope must return nothing
     *
     * @return callable[]
     */
    public function scopes(): array;

    /**
     * Convert the entity to index document
     * The returned value should contains the field _id for store the document id
     *
     * Note: This method is not the opposite method of fromIndex
     *
     * @param object $entity
     * @param string[]|null $attributes List of attributes to update. If null, all attributes will be updated
     *
     * @return array
     *
     * @throws \TypeError If the given entity is not an instance of the handled entity
     */
    public function toIndex($entity, ?array $attributes = null): array;

    /**
     * Convert the indexed document to entity
     *
     * The document should be in the return format of the search API, with keys :
     * - _id for the id
     * - _source for the document attributes
     *
     * @param array $document
     *
     * @return object
     */
    public function fromIndex(array $document);

    /**
     * Extract the id from the entity
     *
     * @param object $entity
     *
     * @return mixed|null The id, or null if the extractor is not configured
     */
    public function id($entity);

    /**
     * Set the id to the entity
     *
     * @param object $entity
     * @param string $id
     *
     * @return void
     */
    public function setId($entity, $id): void;
}
