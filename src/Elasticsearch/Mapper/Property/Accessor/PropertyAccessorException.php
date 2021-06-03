<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor;

use Bdf\Prime\Indexer\Exception\PrimeIndexerException;
use LogicException;

/**
 * The property access is invalid
 */
class PropertyAccessorException extends LogicException implements PrimeIndexerException
{

}
