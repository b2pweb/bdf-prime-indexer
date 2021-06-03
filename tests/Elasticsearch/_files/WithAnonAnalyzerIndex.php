<?php

namespace ElasticsearchTestFiles;

use Bdf\Prime\Entity\Extensions\ArrayInjector;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\CsvAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertiesBuilder;

class WithAnonAnalyzerIndex implements ElasticsearchIndexConfigurationInterface
{
    public function entity(): string
    {
        return WithAnonAnalyzer::class;
    }

    public function index(): string
    {
        return 'test_anon_analyzers';
    }

    public function type(): string
    {
        return 'anon_analyzer';
    }

    public function id(): ?PropertyAccessorInterface
    {
        return null;
    }

    public function properties(PropertiesBuilder $builder): void
    {
        $builder
            ->string('name')
            ->string('values')->analyzer(new CsvAnalyzer(';'))
        ;
    }

    public function analyzers(): array
    {
        return [];
    }

    public function scopes(): array
    {
        return [];
    }
}
