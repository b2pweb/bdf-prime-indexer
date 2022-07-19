<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception;

use RuntimeException;

/**
 * The request is invalid
 */
class InvalidRequestException extends RuntimeException implements ElasticsearchExceptionInterface
{

}
