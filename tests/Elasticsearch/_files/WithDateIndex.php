<?php

namespace ElasticsearchTestFiles;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\SimplePropertyAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertiesBuilder;

class WithDateIndex implements ElasticsearchIndexConfigurationInterface
{
    public function index(): string
    {
        return 'with_date';
    }

    public function id(): ?PropertyAccessorInterface
    {
        return new SimplePropertyAccessor('id');
    }

    public function properties(PropertiesBuilder $builder): void
    {
        $builder->keyword('value');
        $builder->date('date', 'yyyy-MM-dd');
    }

    public function analyzers(): array
    {
        return [];
    }

    public function scopes(): array
    {
        return [];
    }

    public function entity(): string
    {
        return WithDate::class;
    }
}
