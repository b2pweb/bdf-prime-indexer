<?php

namespace ElasticsearchTestFiles;

use Bdf\Prime\Entity\Extensions\ArrayInjector;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\SimplePropertyAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertiesBuilder;
use Bdf\Prime\Indexer\Elasticsearch\Query\Compound\FunctionScoreQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\ElasticsearchQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\Match;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\MatchBoolean;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\QueryString;

class CityIndex implements ElasticsearchIndexConfigurationInterface, \Bdf\Prime\Indexer\ShouldBeIndexedConfigurationInterface
{
    public function index(): string
    {
        return 'test_cities';
    }

    public function type(): string
    {
        return 'city';
    }

    public function entity(): string
    {
        return City::class;
    }

    public function properties(PropertiesBuilder $builder): void
    {
        $builder
            ->text('name')
            ->integer('population')
            ->keyword('zipCode')
            ->keyword('country')->disableIndexing()
            ->boolean('enabled')
        ;
    }

    public function id(): ?PropertyAccessorInterface
    {
        return new SimplePropertyAccessor('id');
    }

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

    public function scopes(): array
    {
        return [
            'default' => function (ElasticsearchQuery $query) {
                $query
                    ->wrap(
                        (new FunctionScoreQuery())
                            ->addFunction('field_value_factor', [
                                'field' => 'population',
                                'factor' => 1,
                                'modifier' => 'log1p'
                            ])
                            ->scoreMode('multiply')
                    )
                    ->filter('enabled', true)
                ;
            },

            'matchName' => function (ElasticsearchQuery $query, string $name) {
                $query
                    ->where(new MatchBoolean('name', $name))
                    ->orWhere(
                        (new QueryString($name.'%'))
                            ->and()
                            ->defaultField('name')
                            ->analyzeWildcard()
                            ->useLikeSyntax()
                    )
                ;
            }
        ];
    }

    public function shouldBeIndexed($entity): bool
    {
        return $entity->enabled();
    }
}
