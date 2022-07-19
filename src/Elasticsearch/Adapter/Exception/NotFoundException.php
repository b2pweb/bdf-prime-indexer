<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception;

use RuntimeException;

/**
 * The requested end point or resource do not exists
 */
class NotFoundException extends RuntimeException implements ElasticsearchExceptionInterface
{

}
