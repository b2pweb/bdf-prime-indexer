<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Property;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapperInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\CustomAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\EmbeddedAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\ReadOnlyAccessor;
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
     * Add a field into the property
     *
     * <code>
     * $builder->string('name')->field('raw', ['type' => string', 'index' => 'not_analyzed']);
     * </code>
     *
     * @param string $name The field name
     * @param array $options
     *
     * @return $this
     */
    public function field(string $name, array $options): PropertiesBuilder
    {
        if (!isset($this->properties[$this->current]['fields'])) {
            $this->properties[$this->current]['fields'] = [$name => $options];
        } else {
            $this->properties[$this->current]['fields'][$name] = $options;
        }

        return $this;
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
     * <code>
     * // Simple accessor : use when indexed and php fields differs
     * $builder->accessor('myPhpField');
     *
     * // Custom accessor
     * // Can be used for computed properties
     * $builder->accessor(function ($entity, $value) {
     *     if ($value === null) { // second parameter null : getter
     *         return $entity->getValue();
     *     }
     *
     *     // setter
     *     $entity->setValue($value);
     * });
     *
     * // Embedded accessor : the field "value" is into the "embedded" object, which is an instance of MyEmbedded
     * $builder->accessor(['embedded' => MyEmbedded::class, 'value']);
     *
     * // Custom accessor instance
     * $builder->accessor(new MyCustomAccessor());
     * </code>
     *
     * @param PropertyAccessorInterface|string|array|callable $accessor The accessor
     *
     * @return $this
     */
    public function accessor($accessor): PropertiesBuilder
    {
        switch (true) {
            case is_string($accessor):
                return $this->option('accessor', new SimplePropertyAccessor($accessor));

            // Check callable after string, but before array for allow [$this, 'method'] syntax, but disallow global functions
            case is_callable($accessor):
                return $this->option('accessor', new CustomAccessor($accessor));

            case is_array($accessor):
                return $this->option('accessor', new EmbeddedAccessor($accessor));

            case $accessor instanceof PropertyAccessorInterface:
                return $this->option('accessor', $accessor);

            default:
                throw new \InvalidArgumentException('Invalid accessor given');
        }
    }

    /**
     * Set the property as read only
     *
     * A read only property is only use for search purpose, but it's not hydrated to the entity.
     * A common use is for computed properties.
     *
     * <code>
     * // Set isActivated as readonly
     * $builder->string('isActivated')->readOnly();
     *
     * // Custom accessor can also be defined
     * // Note: readOnly must be called after set the accessor
     * $builder->accessor(['embedded', 'value'])->readOnly();
     * </code>
     *
     * @return $this
     */
    public function readOnly(): PropertiesBuilder
    {
        if (isset($this->properties[$this->current]['accessor'])) {
            $this->properties[$this->current]['accessor'] = new ReadOnlyAccessor($this->properties[$this->current]['accessor']);
        } else {
            $this->properties[$this->current]['accessor'] = new ReadOnlyAccessor($this->current);
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
