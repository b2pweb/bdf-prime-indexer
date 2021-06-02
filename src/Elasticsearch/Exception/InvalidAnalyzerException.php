<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Exception;

use Bdf\Prime\Indexer\Exception\PrimeIndexerException;
use LogicException;

/**
 * A declared analyzer is invalid
 */
class InvalidAnalyzerException extends LogicException implements PrimeIndexerException
{

}
