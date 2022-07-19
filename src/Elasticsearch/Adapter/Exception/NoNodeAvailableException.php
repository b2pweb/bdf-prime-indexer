<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception;

use RuntimeException;

/**
 * Elasticsearch nodes are down, or host configuration is invalid
 */
class NoNodeAvailableException extends RuntimeException implements ElasticsearchExceptionInterface
{

}
