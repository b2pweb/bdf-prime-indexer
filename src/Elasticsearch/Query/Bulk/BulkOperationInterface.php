<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query\Bulk;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapperInterface;

/**
 * Store single bulk operation arguments
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html#bulk-api-request-body
 */
interface BulkOperationInterface
{
    /**
     * Operation name
     *
     * @return string
     */
    public function name(): string;

    /**
     * Get operation metadata
     * This field should contain (depending on the operation) the document id
     * The '_index' key will be automatically added
     *
     * @param ElasticsearchMapperInterface $mapper Mapper use for extract document id
     *
     * @return array
     */
    public function metadata(ElasticsearchMapperInterface $mapper): array;

    /**
     * Parameters for the operation
     * Should be document fields for create and index operations, and doc for update operation
     *
     * @param ElasticsearchMapperInterface $mapper Mapper use for convert entity to document
     *
     * @return array|null The parameters or null this operation has no parameters
     */
    public function value(ElasticsearchMapperInterface $mapper): ?array;
}
