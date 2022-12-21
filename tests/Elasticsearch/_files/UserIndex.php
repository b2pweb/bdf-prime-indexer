<?php

namespace ElasticsearchTestFiles;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\CsvAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertiesBuilder;

class UserIndex implements ElasticsearchIndexConfigurationInterface
{
    public function entity(): string
    {
        return User::class;
    }

    public function index(): string
    {
        return 'test_users';
    }

    public function type(): string
    {
        return 'user';
    }

    public function id(): ?PropertyAccessorInterface
    {
        return null;
    }

    public function properties(PropertiesBuilder $builder): void
    {
        $builder
            ->text('name')
            ->text('email')
            ->keyword('login')->accessor('email')->disableIndexing()
            ->keyword('password')->disableIndexing()
            ->text('roles')->analyzer('csv');
    }

    public function analyzers(): array
    {
        return [
            'csv' => new CsvAnalyzer()
        ];
    }

    public function scopes(): array
    {
        return [];
    }
}