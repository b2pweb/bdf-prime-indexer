# Prime Indexer

Indexing entities through prime, and request from Elasticsearch index.

## Installation

Install with composer :

```bash
composer require b2p/bdf-prime-indexer
```

Register into application :

```php
<?php
$application = new \Bdf\Web\Application([
    // ...
    'prime.indexes' => [
        MyEntity::class => new MyEntityIndex(),
    ],
    // ...
]);

$application->register(new \Bdf\Prime\PrimeServiceProvider());
$application->register(new \Bdf\Prime\Indexer\PrimeIndexerServiceProvider());
```

Configure elasticsearch on conf.ini

```ini
; ...
; Set the elasticsearch hosts
elasticsearch.hosts[] = "127.0.0.1:9222"
; ...
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

    // Declare the elasticsearch index type
    public function type(): string
    {
        return 'city';
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

After that, the index should be added to the "prime.indexes" container's item :

```php
<?php
$application = new \Bdf\Web\Application([
    // ...
    'prime.indexes' => [
        City::class => new CityIndex(),
    ],
    // ...
]);
```

There is no "magic" resolver for index, so no naming convention for declare the index.

### Querying the index

The query system use Prime interfaces, so usage is almost the same :

```php
<?php
// Get the City index
$index = $application->get(\Bdf\Prime\Indexer\IndexFactory::class)->for(City::class);

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
$index = $application->get(\Bdf\Prime\Indexer\IndexFactory::class)->for(City::class);

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
bin/console.php prime:indexer:create City
``` 

A progress bar will be displayed for follow the indexing progress.

For manage Elasticsearch index :

```
bin/console.php elasticsearch

show
delete test_cities
```

### Synchronization of entities

Automatic synchronization can be configured.
The indexer will listen update operation on repository, and update the index corresponding to the events.

For enable synchronization some dependencies are required :

- `b2p/bdf-prime` For repositories
- `b2p/bdf-bus` For perform index operation asynchronously (or not, depending of the bus configuration)

On PHP side :

```php
<?php
$application = new \Bdf\Web\Application([
    // ...
    'prime.indexes' => [
        City::class => new CityIndex(),
    ],
    // ...
]);

$application->register(new \Bdf\Bus\BusServiceProvider());
$application->register(new \Bdf\Prime\PrimeServiceProvider());
$application->register(new \Bdf\Prime\Indexer\PrimeIndexerServiceProvider());
$application->register(new \Bdf\Prime\Indexer\PrimeIndexerSynchronizationProvider());
```

And now, synchronization is enabled.
Do not forget to start workers !

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
