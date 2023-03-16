# Prime Indexer
[![build](https://github.com/b2pweb/bdf-prime-indexer/actions/workflows/php.yml/badge.svg)](https://github.com/b2pweb/bdf-prime-indexer/actions/workflows/php.yml)
[![codecov](https://codecov.io/github/b2pweb/bdf-prime-indexer/branch/2.0/graph/badge.svg?token=VOFSPEWYKX)](https://app.codecov.io/github/b2pweb/bdf-prime-indexer)
[![Packagist Version](https://img.shields.io/packagist/v/b2pweb/bdf-prime-indexer.svg)](https://packagist.org/packages/b2pweb/bdf-prime-indexer)
[![Total Downloads](https://img.shields.io/packagist/dt/b2pweb/bdf-prime-indexer.svg)](https://packagist.org/packages/b2pweb/bdf-prime-indexer)
[![Type Coverage](https://shepherd.dev/github/b2pweb/bdf-prime-indexer/coverage.svg)](https://shepherd.dev/github/b2pweb/bdf-prime-indexer)

Indexing entities through prime, and request from Elasticsearch index.

## Installation

Install with composer :

```bash
composer require b2pweb/bdf-prime-indexer
```

Register into `config/bundles.php` :

```php
<?php

return [
    // ...
    Bdf\Prime\Indexer\Bundle\PrimeIndexerBundle::class => ['all' => true],
    Bdf\PrimeBundle\PrimeBundle::class => ['all' => true],
];
```

Configure indexes into `config/packages/prime_indexer.yaml` :

```yaml
prime_indexer:
  elasticsearch:
    # Define elasticsearch hosts
    hosts: ['127.0.0.1:9222']

  # Define indexes in form [Entity class]: [Index configuration class]
  # This is not mandatory if autoconfiguration is enabled
  indexes:
    App\Entities\City: App\Entities\CityIndex
    App\Entities\User: App\Entities\UserIndex
```

## Usage

### Declaring index

For declaring an index, you should first declare the configuration :

```php
<?php
class CityIndex implements ElasticsearchIndexConfigurationInterface
{
    // Declare the index name
    public function index(): string
    {
        return 'test_cities';
    }

    // Get the mapped entity type
    public function entity(): string
    {
        return City::class;
    }

    // Build properties
    public function properties(PropertiesBuilder $builder): void
    {
        $builder
            ->string('name')
            ->integer('population')
            ->string('zipCode')
            ->string('country')->notAnalyzed()
            ->boolean('enabled')
        ;
    }

    // The id accessor
    public function id(): ?PropertyAccessorInterface
    {
        return new SimplePropertyAccessor('id');
    }

    // Declare analyzers
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

    // Scopes
    public function scopes(): array
    {
        return [
            // "default" scope is always applied to the query
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

            // Other scope : can be used as custom filter on query
            // Or using $index->myScope()
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
}
```

Some extra configuration can be added by implementing interfaces :

- `CustomEntitiesConfigurationInterface` : For define the entities loading method
- `ShouldBeIndexedConfigurationInterface` : For define predicate which check if an entity should be indexed or not

After that, the index can be added to the "prime_indexer.indexes" configuration, or let the autoconfiguration do the job.

### Querying the index

The query system use Prime interfaces, so usage is almost the same :

```php
<?php
// Get the City index
$index = $container->get(\Bdf\Prime\Indexer\IndexFactory::class)->for(City::class);

// Get the query
$query = $index->query();

$query
    ->where('country', 'FR') // Simple where works as expected
    ->where('name', ':like', 'P%') // "like" operator is supported
    ->orWhere(new QueryString('my complete query')) // Operator object can be used for more powerful filters
;

// Get all cities who match with filters
$query->all();

// First returns the first matching element, wrapped into an Optional
$query->first()->get();

// Get the raw result of the elasticsearch query
$query->execute();

// Use scope directly
$index->matchName('Paris')->all();

// Same as above, but with scope as filter
$index->query()->where('matchName', 'Paris')->all();
```

### Updating the index

Update operations can be done on the index manually :

```php
<?php
// Get the City index
$index = $container->get(\Bdf\Prime\Indexer\IndexFactory::class)->for(City::class);

// Create the index, and insert all cities from database
$index->create(City::walk());

$paris = new City([
    'name' => 'Paris',
    'population' => 2201578,
    'country' => 'FR',
    'zipCode' => '75000'
]);

// Indexing the city
$index->add($paris);

// The "id" property is filled after insertion
echo $paris->id();

// Make sure that index is up to date
// !!! Do not use on production !!!
$index->refresh();

$index->contains($paris); // true

// Update one attribute
$paris->setPopulation(2201984);
$index->update($paris, ['population']); 

// Remove the entity
$index->remove($paris);
$index->contains($paris); // false

// Drop index
$index->drop();
```

### With CLI

Create index, and indexing entities :

```
bin/console.php prime:indexer:create App\Entities\City
```

A progress bar will be displayed for follow the indexing progress.

> Note: The full qualified class name of the entity must be used as argument.

For manage Elasticsearch index :

```
bin/console.php elasticsearch:show
bin/console.php elasticsearch:delete test_cities
```

### Testing

Because testing is one of more important things, an utility class is added for this :

Note : The index name will be prefixed by "test_" to ensure that it will not impact the real index.

```php
<?php
class MyTest extends \Bdf\PHPUnit\WebTestCase
{
    /**
     * @var TestingIndexer
     */
    private $indexTester;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->indexTester = new TestingIndexer($this->app);
        $this->indexTester->index(City::class); // Declare the city index
    }
    
    protected function tearDown() : void
    {
        parent::tearDown();
        
        $this->indexTester->destroy();
    }
    
    public function test_city_index()
    {
        // Push entities to index
        $this->indexTester->push([
            new City(...),
            new City(...),
            new City(...),
        ]);
        
        // Remove from index
        $this->indexTester->remove(new City(...));
        
        // Querying to the index
        $query = $this->indexTester->index(City::class)->query();
    }
}
```

## Interactions and differences with Prime

- Prime is not required to be registered for use index system. Some entities can be into an index, but not in database.
- Unlike Prime, the mapping is index-oriented and not model-oriented :
    - The PropertiesBuilder define the index properties, and maps to the model ones
    - Computed properties are permitted (i.e. properties not stored into the entity)
    - Query filters columns are not mapped, and use the indexed ones
- Queries use streams (from b2pweb/bdf-collections), so first() returns an OptionalInterface, and transformation are done on the stream
