<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Mapper;

use Bdf\Prime\Entity\Instantiator\Instantiator;
use Bdf\Prime\Entity\Instantiator\InstantiatorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\AnalyzerInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\ArrayAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\StandardAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertiesBuilder;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Property;

/**
 * Base elasticsearch mapper class
 */
final class ElasticsearchMapper implements ElasticsearchMapperInterface
{
    /**
     * @var ElasticsearchIndexConfigurationInterface
     */
    private $configuration;

    /**
     * @var InstantiatorInterface
     */
    private $instantiator;

    /**
     * @var Property[]|null
     */
    private $properties = null;

    /**
     * @var AnalyzerInterface[]|null
     */
    private $analyzers = null;

    /**
     * @var PropertyAccessorInterface|null
     */
    private $id = null;

    /**
     * @var callable[]
     */
    private $scopes = null;


    /**
     * ElasticsearchMapper constructor.
     *
     * @param ElasticsearchIndexConfigurationInterface $configuration
     * @param InstantiatorInterface $instantiator
     */
    public function __construct(ElasticsearchIndexConfigurationInterface $configuration, InstantiatorInterface $instantiator = null)
    {
        $this->configuration = $configuration;
        $this->instantiator = $instantiator ?: new Instantiator();
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
        if ($this->properties !== null) {
            return $this->properties;
        }

        $builder = new PropertiesBuilder($this);
        $this->configuration->properties($builder);

        return $this->properties = $builder->build();
    }

    /**
     * {@inheritdoc}
     */
    public function analyzers(): array
    {
        if ($this->analyzers !== null) {
            return $this->analyzers;
        }

        $analyzers = [];

        foreach ($this->configuration->analyzers() as $name => $analyzer) {
            if ($analyzer instanceof AnalyzerInterface) {
                $analyzers[$name] = $analyzer;
            } elseif (is_array($analyzer)) {
                $analyzers[$name] = new ArrayAnalyzer($analyzer);
            } else {
                throw new \LogicException('Invalid analyzer declaration. Expects array or AnalyzerInterface');
            }
        }

        if (!isset($analyzers['default'])) {
            $analyzers['default'] = new StandardAnalyzer();
        }

        return $this->analyzers = $analyzers;
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
    public function toIndex($entity): array
    {
        $className = $this->configuration->entity();

        if (!$entity instanceof $className) {
            throw new \TypeError('Entity must be an instance of '.$className);
        }

        $document = [];

        if ($accessor = $this->idAccessor()) {
            $document['_id'] = $accessor->readFromModel($entity);
        }

        foreach ($this->properties() as $property) {
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
}
