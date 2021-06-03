<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Exception;

use Bdf\Prime\Indexer\Exception\PrimeIndexerException;
use RuntimeException;

/**
 * Invalid elasticsearch query
 */
class ElaticsearchQueryException extends RuntimeException implements PrimeIndexerException
{

}
