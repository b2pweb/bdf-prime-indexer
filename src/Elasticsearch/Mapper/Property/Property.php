<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper\Property;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\AnalyzerInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Transformer\NullPropertyTransformer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Transformer\PropertyTransformerInterface;

/**
 * Store property and index field properties
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.4/mapping-types.html
 */
final class Property implements PropertyInterface
{
    /**
     * @var string
     */
    private string $name;

    /**
     * @var array{
     *     index?: bool,
     *     fields?: array,
     *     analyzer?: string,
     *     ...
     * }
     */
    private array $declaration;

    /**
     * @var AnalyzerInterface
     */
    private AnalyzerInterface $analyzer;

    /**
     * @var string
     */
    private string $type;

    /**
     * @var PropertyAccessorInterface
     */
    private PropertyAccessorInterface $accessor;

    /**
     * @var PropertyTransformerInterface
     */
    private PropertyTransformerInterface $transformer;


    /**
     * Property constructor.
     *
     * @param string $name
     * @param array{index?: bool, fields?: array, analyzer?: string, ...} $declaration
     * @param AnalyzerInterface $analyzer
     * @param string $type
     * @param PropertyAccessorInterface $accessor
     * @param PropertyTransformerInterface|null $transformer
     */
    public function __construct(string $name, array $declaration, AnalyzerInterface $analyzer, string $type, PropertyAccessorInterface $accessor, ?PropertyTransformerInterface $transformer = null)
    {
        $this->name = $name;
        $this->declaration = $declaration;
        $this->analyzer = $analyzer;
        $this->type = $type;
        $this->accessor = $accessor;
        $this->transformer = $transformer ?? NullPropertyTransformer::instance();
    }

    /**
     * Get the indexed property name
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get the property declaration custom settings (excluding type and analyzer)
     *
     * @return array
     */
    public function declaration(): array
    {
        return $this->declaration;
    }

    /**
     * Get the used analyzer for indexing the property
     *
     * @return AnalyzerInterface
     */
    public function analyzer(): AnalyzerInterface
    {
        return $this->analyzer;
    }

    /**
     * Get the property index type
     *
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Get the accessor related to the property
     *
     * @return PropertyAccessorInterface
     */
    public function accessor(): PropertyAccessorInterface
    {
        return $this->accessor;
    }

    /**
     * {@inheritdoc}
     */
    public function readFromModel($entity)
    {
        return $this->analyzer->toIndex(
            $this->transformer->toIndex(
                $this,
                $this->accessor->readFromModel($entity)
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function writeToModel($entity, $indexedValue)
    {
        $this->accessor->writeToModel(
            $entity,
            $this->transformer->fromIndex(
                $this,
                $this->analyzer->fromIndex($indexedValue)
            )
        );
    }
}
