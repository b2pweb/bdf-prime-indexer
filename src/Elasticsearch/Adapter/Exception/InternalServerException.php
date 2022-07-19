<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception;

use RuntimeException;

/**
 * An HTTP 500 internal error has occurred on server
 */
class InternalServerException extends RuntimeException implements ElasticsearchExceptionInterface
{

}
