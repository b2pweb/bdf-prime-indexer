<?php

use Bdf\Prime\Entity\Extensions\ArrayInjector;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\SimplePropertyAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertiesBuilder;
use Bdf\Prime\Indexer\Elasticsearch\Query\Compound\FunctionScoreQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\ElasticsearchQuery;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\Match;
use Bdf\Prime\Indexer\Elasticsearch\Query\Filter\QueryString;
use Bdf\Prime\Indexer\IndexTestCase;

class City
{
    use ArrayInjector;

    private $id;
    private $name;
    private $zipCode;
    private $population;
    private $country;
    private $enabled = true;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }

    /**
     * @return mixed
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function zipCode()
    {
        return $this->zipCode;
    }

    /**
     * @param mixed $zipCode
     *
     * @return $this
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * @return mixed
     */
    public function population()
    {
        return $this->population;
    }

    /**
     * @param mixed $population
     *
     * @return $this
     */
    public function setPopulation($population)
    {
        $this->population = $population;

        return $this;
    }

    /**
     * @return mixed
     */
    public function country()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     *
     * @return $this
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return bool
     */
    public function enabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return $this
     */
    public function setEnabled($enabled): City
    {
        $this->enabled = (bool) $enabled;

        return $this;
    }
}

class CityMapper extends \Bdf\Prime\Mapper\Mapper
{
    public function schema()
    {
        return [
            'connection' => 'test',
            'table' => 'city',
        ];
    }

    public function buildFields($builder)
    {
        $builder
            ->integer('id')->autoincrement()
            ->string('name')
            ->string('zipCode')
            ->integer('population')
            ->string('country')
            ->boolean('enabled')
        ;
    }
}

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
        if (IndexTestCase::minimalElasticsearchVersion('5.0')) {
            $builder
                ->text('name')
                ->integer('population')
                ->keyword('zipCode')
                ->keyword('country')->disableIndexing()
                ->boolean('enabled')
            ;
        } else {
            $builder
                ->string('name')
                ->integer('population')
                ->string('zipCode')
                ->string('country')->notAnalyzed()
                ->boolean('enabled')
            ;
        }
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
                    ->where(new Match('name', $name))
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
