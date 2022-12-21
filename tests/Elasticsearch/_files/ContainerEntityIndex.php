<?php

namespace ElasticsearchTestFiles;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\SimplePropertyAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertiesBuilder;

class ContainerEntityIndex implements ElasticsearchIndexConfigurationInterface
{
    /**
     * @inheritDoc
     */
    public function index(): string
    {
        return 'containers';
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return ContainerEntity::class;
    }

    /**
     * @inheritDoc
     */
    public function id(): ?PropertyAccessorInterface
    {
        return new SimplePropertyAccessor('id');
    }

    /**
     * @inheritDoc
     */
    public function properties(PropertiesBuilder $builder): void
    {
        $builder
            ->text('name')
            ->object('foo', EmbeddedEntity::class, function (PropertiesBuilder $builder) {
                $builder
                    ->keyword('key')
                    ->integer('value')
                ;
            })
            ->object('bar', EmbeddedEntity::class, function (PropertiesBuilder $builder) {
                $builder
                    ->keyword('key')
                    ->integer('value')
                ;
            })
        ;
    }

    /**
     * @inheritDoc
     */
    public function analyzers(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function scopes(): array
    {
        return [];
    }
}
