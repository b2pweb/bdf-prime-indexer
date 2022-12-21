<?php

namespace Bdf\Prime\Indexer\Elasticsearch;

use Bdf\Prime\Indexer\CreateIndexOptions;
use Bdf\Prime\Indexer\Elasticsearch\Query\Bulk\ElasticsearchBulkQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\ElasticsearchCreateQuery;

/**
 * Extends create index options for elasticsearch index
 */
class ElasticsearchCreateIndexOptions extends CreateIndexOptions
{
    /**
     * Refresh the index after insertion of entities ?
     * If true, indexation query will wait synchronously for completion of the indexation
     */
    public bool $refresh = false;

    /**
     * If true, use {@see ElasticsearchBulkQuery} instead of {@see ElasticsearchCreateQuery}
     */
    public bool $useBulkWriteQuery = false;

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $options): self
    {
        $optionsObj = parent::fromArray($options);

        if (($refresh = $options['refresh'] ?? null) !== null) {
            $optionsObj->refresh = $refresh;
        }

        if (($useBulkWriteQuery = $options['useBulkWriteQuery'] ?? null) !== null) {
            $optionsObj->useBulkWriteQuery = $useBulkWriteQuery;
        }

        return $optionsObj;
    }
}
