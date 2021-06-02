<?php

namespace ElasticsearchTestFiles;

use Bdf\Prime\Entity\Extensions\ArrayInjector;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\CsvAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertiesBuilder;

class UserMapper extends \Bdf\Prime\Mapper\Mapper
{
    public function schema()
    {
        return [
            'connection' => 'test',
            'table' => 'user'
        ];
    }

    public function buildFields($builder)
    {
        $builder
            ->string('name')
            ->string('email')->primary()
            ->string('password')
            ->simpleArray('roles')
        ;
    }
}
