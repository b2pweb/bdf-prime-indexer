<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Grammar;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertiesBuilder;

/**
 * List of types for elasticsearch fields
 */
interface Types
{
    /**
     * String field which is analyzed
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.0/text.html
     * @see PropertiesBuilder::text()
     */
    public const TEXT = 'text';

    /**
     * String field which is not analyzed
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.0/keyword.html
     * @see PropertiesBuilder::keyword()
     */
    public const KEYWORD = 'keyword';

    /**
     * 64-bit signed integer field
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/number.html
     * @see PropertiesBuilder::long()
     */
    public const LONG = 'long';

    /**
     * 32-bit signed integer field
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/number.html
     * @see PropertiesBuilder::integer()
     */
    public const INTEGER = 'integer';

    /**
     * 16-bit signed integer field
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/number.html
     * @see PropertiesBuilder::short()
     */
    public const SHORT = 'short';

    /**
     * 8-bit signed integer field
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/number.html
     * @see PropertiesBuilder::byte()
     */
    public const BYTE = 'byte';

    /**
     * 64-bit IEEE 754 floating point field
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/number.html
     * @see PropertiesBuilder::double()
     */
    public const DOUBLE = 'double';

    /**
     * 32-bit IEEE 754 floating point field
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/number.html
     * @see PropertiesBuilder::float()
     */
    public const FLOAT = 'float';

    /**
     * Date string or integer field
     *
     * JSON doesn’t have a date datatype, so dates in Elasticsearch can either be:
     * - strings containing formatted dates, e.g. "2015-01-01" or "2015/01/01 12:10:30".
     * - a long number representing milliseconds-since-the-epoch.
     * - an integer representing seconds-since-the-epoch.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/date.html
     * @see PropertiesBuilder::date()
     */
    public const DATE = 'date';

    /**
     * Boolean field
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/boolean.html
     * @see PropertiesBuilder::boolean()
     */
    public const BOOLEAN = 'boolean';

    /**
     * Binary string field
     * This field stores base64 encoded binary data, and it's not indexed nor searchable.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/binary.html
     * @see PropertiesBuilder::binary()
     */
    public const BINARY = 'binary';

    /**
     * JSON object field
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/7.17/object.html
     * @see PropertiesBuilder::object()
     */
    public const OBJECT = 'object';

    /**
     * JSON object field, but not flattened on index
     *
     * https://www.elastic.co/guide/en/elasticsearch/reference/7.17/nested.html
     * @see PropertiesBuilder::nested()
     */
    public const NESTED = 'nested';
}
