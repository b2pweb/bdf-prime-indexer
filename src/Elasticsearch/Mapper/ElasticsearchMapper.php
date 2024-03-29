<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper;

use Bdf\Prime\Entity\Instantiator\Instantiator;
use Bdf\Prime\Entity\Instantiator\InstantiatorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\AnalyzerInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\ArrayAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\StandardAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertiesBuilder;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertyInterface;
use Bdf\Prime\Indexer\Exception\IndexConfigurationException;
use TypeError;

/**
 * Base elasticsearch mapper class
 */
final class ElasticsearchMapper implements ElasticsearchMapperInterface
{
    /**
     * @var ElasticsearchIndexConfigurationInterface
     */
    private ElasticsearchIndexConfigurationInterface $configuration;

    /**
     * @var InstantiatorInterface
     */
    private InstantiatorInterface $instantiator;

    /**
     * @var array<string, PropertyInterface>
     */
    private array $properties;

    /**
     * @var array<string, AnalyzerInterface>
     */
    private array $analyzers;

    /**
     * @var PropertyAccessorInterface|null
     */
    private ?PropertyAccessorInterface $id = null;

    /**
     * @var callable[]
     */
    private ?array $scopes = null;


    /**
     * ElasticsearchMapper constructor.
     *
     * @param ElasticsearchIndexConfigurationInterface $configuration
     * @param InstantiatorInterface|null $instantiator
     *
     * @throws IndexConfigurationException When mapper build failed
     */
    public function __construct(ElasticsearchIndexConfigurationInterface $configuration, ?InstantiatorInterface $instantiator = null)
    {
        $this->configuration = $configuration;
        $this->instantiator = $instantiator ?? new Instantiator();

        $this->build();
    }

    /**
     * {@inheritdoc}
     */
    public function configuration(): ElasticsearchIndexConfigurationInterface
    {
        return $this->configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function properties(): array
    {
        return $this->properties;
    }

    /**
     * {@inheritdoc}
     */
    public function analyzers(): array
    {
        return $this->analyzers;
    }

    /**
     * {@inheritdoc}
     */
    public function scopes(): array
    {
        if ($this->scopes !== null) {
            return $this->scopes;
        }

        return $this->scopes = $this->configuration->scopes();
    }

    /**
     * {@inheritdoc}
     */
    public function toIndex($entity, ?array $attributes = null): array
    {
        $className = $this->configuration->entity();

        if (!$entity instanceof $className) {
            throw new TypeError('Entity must be an instance of ' . $className);
        }

        $document = [];

        if ($accessor = $this->idAccessor()) {
            $document['_id'] = $accessor->readFromModel($entity);
        }

        $properties = $this->properties();

        if ($attributes !== null) {
            $properties = array_intersect_key($this->properties(), array_flip($attributes));
        }

        foreach ($properties as $property) {
            $document[$property->name()] = $property->readFromModel($entity);
        }

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function fromIndex(array $document)
    {
        $properties = $this->properties();
        $entity = $this->instantiate();

        if ($accessor = $this->idAccessor()) {
            $accessor->writeToModel($entity, $document['_id']);
        }

        foreach ($document['_source'] as $property => $value) {
            if (!isset($properties[$property])) {
                continue;
            }

            $properties[$property]->writeToModel($entity, $value);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function id($entity)
    {
        if ($accessor = $this->idAccessor()) {
            return $accessor->readFromModel($entity);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setId($entity, $id): void
    {
        if ($accessor = $this->idAccessor()) {
            $accessor->writeToModel($entity, $id);
        }
    }

    /**
     * Instantiate the entity
     *
     * @return object
     */
    private function instantiate()
    {
        // @todo hint
        return $this->instantiator->instantiate($this->configuration->entity());
    }

    /**
     * Get the id accessor (can be null)
     *
     * @return PropertyAccessorInterface|null
     */
    private function idAccessor(): ?PropertyAccessorInterface
    {
        if ($this->id !== null) {
            return $this->id;
        }

        return $this->id = $this->configuration->id();
    }

    /**
     * Build the mapper
     *
     * @throws IndexConfigurationException When mapper build failed
     */
    private function build(): void
    {
        $this->analyzers = $this->buildAnalyzers();
        $this->properties = $this->buildProperties();
    }

    /**
     * @return array<string, PropertyInterface>
     * @throws IndexConfigurationException When mapper build failed
     */
    private function buildProperties(): array
    {
        $builder = new PropertiesBuilder($this);
        $this->configuration->properties($builder);

        // Add anonymous analyzers
        if (!empty($builder->analyzers())) {
            $this->analyzers += $builder->analyzers();
        }

        return $builder->build();
    }

    /**
     * @return array<string, AnalyzerInterface>
     * @throws IndexConfigurationException When mapper build failed
     */
    private function buildAnalyzers(): array
    {
        $analyzers = [];

        foreach ($this->configuration->analyzers() as $name => $analyzer) {
            if ($analyzer instanceof AnalyzerInterface) {
                $analyzers[$name] = $analyzer;
            } elseif (is_array($analyzer)) {
                $analyzers[$name] = new ArrayAnalyzer($analyzer);
            } else {
                throw new IndexConfigurationException('Invalid analyzer declaration. Expects array or AnalyzerInterface');
            }
        }

        if (!isset($analyzers['default'])) {
            $analyzers['default'] = new StandardAnalyzer();
        }

        return $analyzers;
    }
}
