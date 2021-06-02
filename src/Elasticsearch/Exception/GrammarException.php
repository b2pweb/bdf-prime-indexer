<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Exception;

use Bdf\Prime\Indexer\Exception\PrimeIndexerException;
use UnexpectedValueException;

/**
 * Cannot generate a valid elasticsearch request
 */
class GrammarException extends UnexpectedValueException implements PrimeIndexerException
{

}
