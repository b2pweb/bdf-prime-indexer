<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Property;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapperInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\SimplePropertyAccessor;

/**
 * Build index properties
 */
class PropertiesBuilder
{
    /**
     * @var ElasticsearchMapperInterface
     */
    private $mapper;

    /**
     * @var array
     */
    private $properties = [];

    /**
     * The current property name
     *
     * @var string
     */
    private $current;

    /**
     * PropertiesBuilder constructor.
     *
     * @param ElasticsearchMapperInterface $mapper
     */
    public function __construct(ElasticsearchMapperInterface $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * Add a new property into the index
     *
     * @param string $name The index property name
     * @param string $type The type name
     *
     * @return $this
     */
    public function add(string $name, string $type): PropertiesBuilder
    {
        $this->current = $name;
        $this->properties[$name] = ['type' => $type];

        return $this;
    }

    /**
     * Add a string property
     *
     * @param string $name The index property name
     *
     * @return $this
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/string.html
     */
    public function string(string $name): PropertiesBuilder
    {
        return $this->add($name, 'string');
    }

    /**
     * Add a long property
     * A signed 64-bit integer with a minimum value of -263 and a maximum value of 263-1.
     *
     * @param string $name The index property name
     *
     * @return $this
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/number.html
     */
    public function long(string $name): PropertiesBuilder
    {
        return $this->add($name, 'long');
    }

    /**
     * Add a integer property
     * A signed 32-bit integer with a minimum value of -231 and a maximum value of 231-1.
     *
     * @param string $name The index property name
     *
     * @return $this
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/number.html
     */
    public function integer(string $name): PropertiesBuilder
    {
        return $this->add($name, 'integer');
    }

    /**
     * Add a short property
     * A signed 16-bit integer with a minimum value of -32,768 and a maximum value of 32,767.
     *
     * @param string $name The index property name
     *
     * @return $this
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/number.html
     */
    public function short(string $name): PropertiesBuilder
    {
        return $this->add($name, 'short');
    }

    /**
     * Add a byte property
     * A signed 8-bit integer with a minimum value of -128 and a maximum value of 127.
     *
     * @param string $name The index property name
     *
     * @return $this
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/number.html
     */
    public function byte(string $name): PropertiesBuilder
    {
        return $this->add($name, 'byte');
    }

    /**
     * Add a double property
     * A double-precision 64-bit IEEE 754 floating point.
     *
     * @param string $name The index property name
     *
     * @return $this
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/number.html
     */
    public function double(string $name): PropertiesBuilder
    {
        return $this->add($name, 'double');
    }

    /**
     * Add a float property
     * A single-precision 32-bit IEEE 754 floating point.
     *
     * @param string $name The index property name
     *
     * @return $this
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/number.html
     */
    public function float(string $name): PropertiesBuilder
    {
        return $this->add($name, 'float');
    }

    /**
     * Add a date property
     *
     * JSON doesn’t have a date datatype, so dates in Elasticsearch can either be:
     * - strings containing formatted dates, e.g. "2015-01-01" or "2015/01/01 12:10:30".
     * - a long number representing milliseconds-since-the-epoch.
     * - an integer representing seconds-since-the-epoch.
     *
     * @param string $name The index property name
     *
     * @return $this
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/date.html
     */
    public function date(string $name): PropertiesBuilder
    {
        return $this->add($name, 'date');
    }

    /**
     * Add a boolean property
     *
     * @param string $name The index property name
     *
     * @return $this
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/boolean.html
     */
    public function boolean(string $name): PropertiesBuilder
    {
        return $this->add($name, 'boolean');
    }

    /**
     * Add a binary property
     * The binary type accepts a binary value as a Base64 encoded string.
     * The field is not stored by default and is not searchable.
     *
     * @param string $name The index property name
     *
     * @return $this
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/binary.html
     */
    public function binary(string $name): PropertiesBuilder
    {
        return $this->add($name, 'binary');
    }

    /**
     * Configure the analyzer name for the property
     *
     * @param string $name
     *
     * @return $this
     */
    public function analyzer(string $name): PropertiesBuilder
    {
        if (!isset($this->mapper->analyzers()[$name])) {
            throw new \InvalidArgumentException('Analyzer '.$name.' is not declared');
        }

        return $this->option('analyzer', $name);
    }

    /**
     * Disable full-text analysis on the property
     * Use this to permit term level queries
     *
     * @return $this
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/mapping-index.html
     */
    public function notAnalyzed(): PropertiesBuilder
    {
        return $this->option('index', 'not_analyzed');
    }

    /**
     * Configure an option for the property
     *
     * @param string $name The option name
     * @param mixed $value The option value
     *
     * @return $this
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/mapping-params.html
     */
    public function option(string $name, $value): PropertiesBuilder
    {
        $this->properties[$this->current][$name] = $value;

        return $this;
    }

    /**
     * Define the accessor for the property
     *
     * @param PropertyAccessorInterface|string $accessor
     *
     * @return $this
     */
    public function accessor($accessor): PropertiesBuilder
    {
        if (is_string($accessor)) {
            $this->option('accessor', new SimplePropertyAccessor($accessor));
        } elseif ($accessor instanceof PropertyAccessorInterface) {
            $this->option('accessor', $accessor);
        } else {
            throw new \InvalidArgumentException('Invalid accessor given');
        }

        return $this;
    }

    /**
     * Build the properties
     *
     * @return Property[]
     */
    public function build(): array
    {
        $properties = [];

        foreach ($this->properties as $name => $property) {
            $type = $property['type'];
            unset($property['type']);

            $accessor = $property['accessor'] ?? new SimplePropertyAccessor($name);
            unset($property['accessor']);

            $analyzer = $this->mapper->analyzers()[$property['analyzer'] ?? 'default'];

            $properties[$name] = new Property($name, $property, $analyzer, $type, $accessor);
        }

        return $properties;
    }
}
