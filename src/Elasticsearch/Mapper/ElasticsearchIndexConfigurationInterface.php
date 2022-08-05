<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertiesBuilder;
use Bdf\Prime\Indexer\IndexConfigurationInterface;

/**
 * Configuration for an elasticsearch index
 */
interface ElasticsearchIndexConfigurationInterface extends IndexConfigurationInterface
{
    /**
     * Defines the index name
     *
     * @return string
     */
    public function index(): string;

    /**
     * Accessor for get or set the document id (_id property)
     * Returns null for not store the id (not recommended)
     *
     * <code>
     * public function id(): ?PropertyAccessorInterface
     * {
     *     return new SimplePropertyAccessor('id');
     * }
     * </code>
     *
     * @return PropertyAccessorInterface
     */
    public function id(): ?PropertyAccessorInterface;

    /**
     * Build the entity properties
     *
     * <code>
     * public function properties(PropertiesBuilder $builder): void
     * {
     *     $builder
     *         ->string('name')
     *         ->integer('population')
     *         ->string('zipCode')
     *         ->string('country')->notAnalyzed()
     *     ;
     * }
     * </code>
     *
     * @param PropertiesBuilder $builder
     */
    public function properties(PropertiesBuilder $builder): void;

    /**
     * Get list of declared analyzers
     * The analyzers are indexed by name
     *
     * @return array
     */
    public function analyzers(): array;

    /**
     * Get list of scopes, indexed by name
     *
     * A scope configure a query
     * If 'default' scope is declared, it will be applied to all queries (including other scopes).
     * To call a scope, use magic method call on the index instance, or use custom filters on query
     *
     * Note: The behavior of scope is different from Prime's scopes :
     *       - For prime it's a repository method extension, which could execute the query and returns the result
     *       - For indexer, more like prime's filters, it's for configure the query
     *
     * <code>
     * public function scopes(): array
     * {
     *     return [
     *         // Declare constraints on the default scope
     *         'default' => function (ElasticsearchQuery $query) {
     *             $query->filter('country', 'FR');
     *         },
     *
     *         // Named scope
     *         'search' => function (ElasticsearchQuery $query, string $name) {
     *             $query->where(new Match('name', $name));
     *         }
     *     ];
     * }
     *
     * // Usage :
     * $index->query()->all(); // Apply default scope
     * $index->search('John')->all(); // get all documents with name = John, and country = FR
     * $index->query(false)->all(); // Disable default scope
     * $index->query(false)->where('search', 'John')->all(); // Use disable default scope, and use with filter
     * </code>
     *
     * @return callable[]
     */
    public function scopes(): array;
}
