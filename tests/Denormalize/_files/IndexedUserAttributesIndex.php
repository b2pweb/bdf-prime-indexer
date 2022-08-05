<?php

namespace DenormalizeTestFiles;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\SimplePropertyAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertiesBuilder;
use Bdf\Prime\Indexer\Elasticsearch\Query\ElasticsearchQuery;

class IndexedUserAttributesIndex implements ElasticsearchIndexConfigurationInterface
{
    /**
     * @inheritDoc
     */
    public function index(): string
    {
        return 'user_attr';
    }

    /**
     * @inheritDoc
     */
    public function type(): string
    {
        return 'user_attr';
    }

    /**
     * @inheritDoc
     */
    public function id(): ?PropertyAccessorInterface
    {
        return new SimplePropertyAccessor('userId');
    }

    /**
     * @inheritDoc
     */
    public function properties(PropertiesBuilder $builder): void
    {
        $builder
            ->integer('userId')
            ->keyword('attributes')->disableIndexing()->accessor(function (IndexedUserAttributes $entity, $value) {
                if ($value === null) {
                    return json_encode($entity->attributes);
                }

                $entity->attributes = json_decode($value, true);
            })
            ->text('keys')
            ->text('values')
            ->text('tags')
        ;
    }

    /**
     * @inheritDoc
     */
    public function analyzers(): array
    {
        return [
            'default' => [
                'type'      => 'custom',
                'tokenizer' => 'standard',
                'filter'    => ['lowercase', 'asciifolding'],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function scopes(): array
    {
        return [
            'byTag' => function (ElasticsearchQuery $query, string $tag) {
                $query->where('tags', $tag);
            },
        ];
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return IndexedUserAttributes::class;
    }
}
