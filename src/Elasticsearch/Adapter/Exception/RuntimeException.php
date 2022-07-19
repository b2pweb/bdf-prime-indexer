<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception;

/**
 * Base exception for any runtime error on elasticsearch client adapter
 */
class RuntimeException extends \RuntimeException implements ElasticsearchExceptionInterface
{

}
