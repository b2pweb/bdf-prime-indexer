<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Property;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\AnalyzerInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;

/**
 * Store property and index field properties
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/mapping-types.html
 */
final class Property
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $declaration;

    /**
     * @var AnalyzerInterface
     */
    private $analyzer;

    /**
     * @var string
     */
    private $type;

    /**
     * @var PropertyAccessorInterface
     */
    private $accessor;


    /**
     * Property constructor.
     *
     * @param string $name
     * @param array $declaration
     * @param AnalyzerInterface $analyzer
     * @param string $type
     * @param PropertyAccessorInterface $accessor
     */
    public function __construct($name, array $declaration, AnalyzerInterface $analyzer, $type, PropertyAccessorInterface $accessor)
    {
        $this->name = $name;
        $this->declaration = $declaration;
        $this->analyzer = $analyzer;
        $this->type = $type;
        $this->accessor = $accessor;
    }

    /**
     * Get the indexed property name
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Get the property declaration custom settings (excluding type and analyzer)
     *
     * @return array
     */
    public function declaration()
    {
        return $this->declaration;
    }

    /**
     * Get the used analyzer for indexing the property
     *
     * @return AnalyzerInterface
     */
    public function analyzer()
    {
        return $this->analyzer;
    }

    /**
     * Get the property index type
     *
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Get the accessor related to the property
     *
     * @return PropertyAccessorInterface
     */
    public function accessor()
    {
        return $this->accessor;
    }
}
