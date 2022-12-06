<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Property;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\AnalyzerInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\ArrayAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\CsvAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapperInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\CustomAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\EmbeddedAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\ReadOnlyAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\SimplePropertyAccessor;
use Bdf\Prime\Indexer\Exception\IndexConfigurationException;
use InvalidArgumentException;

/**
 * Build index properties
 */
class PropertiesBuilder
{
    /**
     * @var ElasticsearchMapperInterface
     */
    private ElasticsearchMapperInterface $mapper;

    /**
     * @var array<string, array{
     *     type: string,
     *     analyzer?: string,
     *     index?: bool,
     *     accessor?: PropertyAccessorInterface,
     *     fields?: array,
     *     properties?: array,
     *     className?: class-string,
     * }>
     */
    private array $properties = [];

    /**
     * Anonymous analyzers
     *
     * @var array<string, AnalyzerInterface>
     */
    private array $analyzers = [];

    /**
     * The current property name
     */
    private ?string $current = null;

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
     * Add a text property
     *
     * @param string $name The index property name
     *
     * @return $this
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.0/text.html
     */
    public function text(string $name): PropertiesBuilder
    {
        return $this->add($name, 'text');
    }

    /**
     * Add a keyword property
     *
     * @param string $name The index property name
     *
     * @return $this
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.0/keyword.html
     */
    public function keyword(string $name): PropertiesBuilder
    {
        return $this->add($name, 'keyword');
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
     * Declare an `object` property
     *
     * The object property allow to declare an embedded object on the indexed document.
     * This object is handled as sub-document, with a class and declared properties.
     *
     * <code>
     * $builder->object('address', Address::class, function (PropertiesBuilder $builder) {
     *     $builder
     *         ->text('name')
     *         ->text('address')
     *         ->keyword('zipCode')
     *         ->keyword('country')
     *     ;
     * });
     * </code>
     *
     * @param string $name The indexed property name
     * @param class-string $className Class name of the embedded object
     * @param callable(PropertiesBuilder):void $configurator Configurator callback for embedded object properties
     *
     * @return $this
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/7.17/object.html
     */
    public function object(string $name, string $className, callable $configurator): PropertiesBuilder
    {
        $properties = clone $this;
        $properties->properties = [];

        $configurator($properties);

        $this->properties[$name] = [
            'type' => 'object',
            'properties' => $properties->build(),
            'className' => $className,
        ];

        return $this;
    }

    /**
     * Helper for handle an array property indexed as CSV
     * The value will be transformed, using CsvAnalyzer to CSV
     *
     * The analyzer will be added automatically, and reused if possible
     *
     * @param string $name The property name. Should be an array property
     * @param string $separator The values separator
     * @param string $type The property type to use. Use "string" on elasticsearch < 5.0
     *
     * @return $this
     *
     * @see CsvAnalyzer The internally used analyzer
     */
    public function csv(string $name, string $separator = ',', string $type = 'text'): PropertiesBuilder
    {
        $this->add($name, $type);

        $analyzerName = 'csv_' . ord($separator);

        if (!isset($this->analyzers[$analyzerName])) {
            $this->analyzers[$analyzerName] = new CsvAnalyzer($separator);
        }

        $this->option('analyzer', $analyzerName);

        return $this;
    }

    /**
     * Configure the analyzer for the property
     *
     * You can pass a string for use an analyzer declared into @see ElasticsearchIndexConfigurationInterface::analyzers()
     * You can also use an anonymous analyzer by passing an AnalyzerInterface instance
     * or an array, which will be cast to ArrayAnalyzer
     * With an anonymous analyzer, the analyzer will be declared using a generated name
     *
     * @param string|array|AnalyzerInterface $analyzer The analyzer name, or value
     *
     * @return $this
     *
     * @throws IndexConfigurationException When invalid analyzer is given
     */
    public function analyzer($analyzer): PropertiesBuilder
    {
        assert($this->current !== null);

        if (is_string($analyzer)) {
            if (!isset($this->mapper->analyzers()[$analyzer])) {
                throw new IndexConfigurationException('Analyzer ' . $analyzer . ' is not declared');
            }
        } else {
            if (is_array($analyzer)) {
                $analyzer = new ArrayAnalyzer($analyzer);
            }

            if (!$analyzer instanceof AnalyzerInterface) {
                throw new IndexConfigurationException('The parameter $analyzer must be a valid analyzer');
            }

            $name = $this->current . '_anon_analyzer';
            $this->analyzers[$name] = $analyzer;
            $analyzer = $name;
        }

        return $this->option('analyzer', $analyzer);
    }

    /**
     * Disable full-text analysis on the property
     * Use this to permit term level queries
     *
     * @return $this
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.0/mapping-index.html
     */
    public function disableIndexing(): PropertiesBuilder
    {
        return $this->option('index', false);
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
        assert($this->current !== null);

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
     * @psalm-suppress PropertyTypeCoercion
     */
    public function option(string $name, $value): PropertiesBuilder
    {
        assert($this->current !== null);
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
     *
     * @throws IndexConfigurationException When invalid accessor is given
     */
    public function accessor($accessor): PropertiesBuilder
    {
        switch (true) {
            case is_string($accessor):
                return $this->option('accessor', new SimplePropertyAccessor($accessor));

            // Check callable after string, but before array for allow [$this, 'method'] syntax,
            // but disallow global functions
            case is_callable($accessor):
                return $this->option('accessor', new CustomAccessor($accessor));

            case is_array($accessor):
                return $this->option('accessor', new EmbeddedAccessor($accessor));

            case $accessor instanceof PropertyAccessorInterface:
                return $this->option('accessor', $accessor);

            default:
                throw new IndexConfigurationException('Invalid accessor given');
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
        assert($this->current !== null);

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
     * @return array<string, PropertyInterface>
     */
    public function build(): array
    {
        $properties = [];

        foreach ($this->properties as $name => $property) {
            $type = $property['type'];
            unset($property['type']);

            $accessor = $property['accessor'] ?? new SimplePropertyAccessor($name);
            unset($property['accessor']);

            if ($type === 'object') {
                $properties[$name] = new ObjectProperty($name, $property['className'] ?? \stdClass::class, $property['properties'] ?? [], $accessor);
            } else {
                if (isset($property['analyzer'], $this->analyzers[$property['analyzer']])) {
                    $analyzer = $this->analyzers[$property['analyzer']];
                } else {
                    $analyzer = $this->mapper->analyzers()[$property['analyzer'] ?? 'default'];
                }

                $properties[$name] = new Property($name, $property, $analyzer, $type, $accessor);
            }
        }

        return $properties;
    }

    /**
     * Get all anonymous analysers
     *
     * @return array<string, AnalyzerInterface>
     */
    public function analyzers(): array
    {
        return $this->analyzers;
    }
}
