<?php

namespace Bdf\Prime\Indexer\Test;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertiesBuilder;

/**
 * Decorate elasticsearch index configuration for testing
 *
 * @template E as object
 * @implements ElasticsearchIndexConfigurationInterface<E>
 */
class ElasticsearchTestingIndexConfig implements ElasticsearchIndexConfigurationInterface
{
    /**
     * @var ElasticsearchIndexConfigurationInterface<E>
     */
    private ElasticsearchIndexConfigurationInterface $config;

    /**
     * ElasticsearchTestingIndexConfig constructor.
     *
     * @param ElasticsearchIndexConfigurationInterface<E> $config
     */
    public function __construct(ElasticsearchIndexConfigurationInterface $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function index(): string
    {
        return 'test_' . $this->config->index();
    }

    /**
     * {@inheritdoc}
     */
    public function entity(): string
    {
        return $this->config->entity();
    }

    /**
     * {@inheritdoc}
     */
    public function id(): ?PropertyAccessorInterface
    {
        return $this->config->id();
    }

    /**
     * {@inheritdoc}
     */
    public function properties(PropertiesBuilder $builder): void
    {
        $this->config->properties($builder);
    }

    /**
     * {@inheritdoc}
     */
    public function analyzers(): array
    {
        return $this->config->analyzers();
    }

    /**
     * {@inheritdoc}
     */
    public function scopes(): array
    {
        return $this->config->scopes();
    }
}
