<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Query;

use Bdf\Prime\Indexer\Elasticsearch\ElasticsearchIndex;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapper;
use Bdf\Prime\Indexer\Elasticsearch\Query\Expression\Script;
use Bdf\Prime\Indexer\Exception\InvalidQueryException;
use Bdf\Prime\Indexer\IndexTestCase;
use ElasticsearchTestFiles\City;
use ElasticsearchTestFiles\CityIndex;
use stdClass;

class ElasticsearchUpdateQueryTest extends IndexTestCase
{
    private ElasticsearchUpdateQuery $query;
    private ElasticsearchMapper $mapper;

    protected function setUp(): void
    {
        $this->query = new ElasticsearchUpdateQuery(
            self::getClient(),
            $this->mapper = new ElasticsearchMapper(new CityIndex())
        );

        $this->query->from('test_cities');
    }

    protected function tearDown(): void
    {
        if (self::getClient()->hasIndex('test_cities')) {
            self::getClient()->deleteIndex('test_cities');
        }
    }

    public function test_update_simple()
    {
        $index = new ElasticsearchIndex(self::getClient(), $this->mapper);
        $index->add($marseille = new City([
            'name' => 'Marseille',
            'zipCode' => '13001',
            'population' => 861635,
            'country' => 'FR'
        ]));
        $index->add($paris = new City([
            'name' => 'Paris',
            'zipCode' => '75001',
            'population' => 2161000,
            'country' => 'FR'
        ]));
        $index->refresh();

        $this->assertTrue(
            $this->query->id($marseille->id())
                ->document(['population' => 987654])
                ->execute()
        );

        $this->assertEquals([
            'doc' => ['population' => 987654]
        ], $this->query->compile());

        $index->refresh();

        $this->assertEqualsCanonicalizing([
            [
                'name' => 'Marseille',
                'zipCode' => '13001',
                'population' => 987654,
                'country' => 'FR',
                'enabled' => true,
            ],
            [
                'name' => 'Paris',
                'zipCode' => '75001',
                'population' => 2161000,
                'country' => 'FR',
                'enabled' => true,
            ],
        ], $this->search()->all());

        $this->assertFalse(
            $this->query->id('not_found')
                ->document(['population' => 987654])
                ->execute()
        );
    }

    public function test_update_with_id_field_on_document()
    {
        $index = new ElasticsearchIndex(self::getClient(), $this->mapper);
        $index->add($marseille = new City([
            'name' => 'Marseille',
            'zipCode' => '13001',
            'population' => 861635,
            'country' => 'FR'
        ]));
        $index->refresh();

        $this->assertTrue(
            $this->query
                ->document(['population' => 987654, '_id' => $marseille->id()])
                ->execute()
        );

        $this->assertEquals([
            'doc' => ['population' => 987654]
        ], $this->query->compile());

        $index->refresh();

        $this->assertEqualsCanonicalizing([
            [
                'name' => 'Marseille',
                'zipCode' => '13001',
                'population' => 987654,
                'country' => 'FR',
                'enabled' => true,
            ],
        ], $this->search()->all());
    }

    public function test_update_with_entity()
    {
        $index = new ElasticsearchIndex(self::getClient(), $this->mapper);
        $index->add($marseille = new City([
            'name' => 'Marseille',
            'zipCode' => '13001',
            'population' => 861635,
            'country' => 'FR'
        ]));
        $index->add($paris = new City([
            'name' => 'Paris',
            'zipCode' => '75001',
            'population' => 2161000,
            'country' => 'FR'
        ]));
        $index->refresh();

        $this->assertTrue(
            $this->query
                ->document($marseille->setPopulation(987654))
                ->execute()
        );

        $this->assertEquals([
            'doc' => [
                'name' => 'Marseille',
                'zipCode' => '13001',
                'population' => 987654,
                'country' => 'FR',
                'enabled' => true,
            ]
        ], $this->query->compile());

        $index->refresh();

        $this->assertEqualsCanonicalizing([
            [
                'name' => 'Marseille',
                'zipCode' => '13001',
                'population' => 987654,
                'country' => 'FR',
                'enabled' => true,
            ],
            [
                'name' => 'Paris',
                'zipCode' => '75001',
                'population' => 2161000,
                'country' => 'FR',
                'enabled' => true,
            ],
        ], $this->search()->all());
    }

    public function test_upsert_simple()
    {
        $index = new ElasticsearchIndex(self::getClient(), $this->mapper);
        $index->add($paris = new City([
            'name' => 'Paris',
            'zipCode' => '75001',
            'population' => 2161000,
            'country' => 'FR'
        ]));

        $marseille = new City([
            'name' => 'Marseille',
            'zipCode' => '13001',
            'population' => 861635,
            'country' => 'FR'
        ]);

        $index->refresh();

        $this->assertTrue(
            $this->query
                ->id('not_found')
                ->document($marseille)
                ->upsert()
                ->execute()
        );

        $this->assertEquals([
            'doc' => [
                'name' => 'Marseille',
                'zipCode' => '13001',
                'population' => 861635,
                'country' => 'FR',
                'enabled' => true,
            ],
            'doc_as_upsert' => true,
        ], $this->query->compile());

        $index->refresh();

        $this->assertEqualsCanonicalizing([
            [
                'name' => 'Marseille',
                'zipCode' => '13001',
                'population' => 861635,
                'country' => 'FR',
                'enabled' => true,
            ],
            [
                'name' => 'Paris',
                'zipCode' => '75001',
                'population' => 2161000,
                'country' => 'FR',
                'enabled' => true,
            ],
        ], $this->search()->all());

        $this->assertTrue(
            $this->query
                ->id('not_found')
                ->document($marseille->setPopulation(987654))
                ->upsert()
                ->execute()
        );
        $index->refresh();

        $this->assertEqualsCanonicalizing([
            [
                'name' => 'Marseille',
                'zipCode' => '13001',
                'population' => 987654,
                'country' => 'FR',
                'enabled' => true,
            ],
            [
                'name' => 'Paris',
                'zipCode' => '75001',
                'population' => 2161000,
                'country' => 'FR',
                'enabled' => true,
            ],
        ], $this->search()->all());
    }

    public function test_update_script()
    {
        $index = new ElasticsearchIndex(self::getClient(), $this->mapper);
        $index->add($marseille = new City([
            'name' => 'Marseille',
            'zipCode' => '13001',
            'population' => 861635,
            'country' => 'FR'
        ]));
        $index->add($paris = new City([
            'name' => 'Paris',
            'zipCode' => '75001',
            'population' => 2161000,
            'country' => 'FR'
        ]));
        $index->refresh();

        $this->assertTrue(
            $this->query->id($marseille->id())
                ->script(new Script('ctx._source.population += 1234'))
                ->execute()
        );

        $this->assertEquals([
            'script' => new Script('ctx._source.population += 1234'),
        ], $this->query->compile());

        $index->refresh();

        $this->assertEqualsCanonicalizing([
            [
                'name' => 'Marseille',
                'zipCode' => '13001',
                'population' => 862869,
                'country' => 'FR',
                'enabled' => true,
            ],
            [
                'name' => 'Paris',
                'zipCode' => '75001',
                'population' => 2161000,
                'country' => 'FR',
                'enabled' => true,
            ],
        ], $this->search()->all());

        $this->assertTrue(
            $this->query->id($marseille->id())
                ->script('ctx._source.population += 1')
                ->execute()
        );
        $index->refresh();

        $this->assertEqualsCanonicalizing([
            [
                'name' => 'Marseille',
                'zipCode' => '13001',
                'population' => 862870,
                'country' => 'FR',
                'enabled' => true,
            ],
            [
                'name' => 'Paris',
                'zipCode' => '75001',
                'population' => 2161000,
                'country' => 'FR',
                'enabled' => true,
            ],
        ], $this->search()->all());

        $this->assertTrue(
            $this->query->id($marseille->id())
                ->script(new Script('ctx._source.population += params.c', Script::LANG_PAINLESS, ['c' => 10]))
                ->execute()
        );
        $index->refresh();

        $this->assertEqualsCanonicalizing([
            [
                'name' => 'Marseille',
                'zipCode' => '13001',
                'population' => 862880,
                'country' => 'FR',
                'enabled' => true,
            ],
            [
                'name' => 'Paris',
                'zipCode' => '75001',
                'population' => 2161000,
                'country' => 'FR',
                'enabled' => true,
            ],
        ], $this->search()->all());

        $this->assertFalse(
            $this->query->id('not_found')
                ->execute()
        );
    }

    public function test_upsert_with_script()
    {
        $index = new ElasticsearchIndex(self::getClient(), $this->mapper);

        $marseille = new City([
            'name' => 'Marseille',
            'zipCode' => '13001',
            'population' => 861635,
            'country' => 'FR'
        ]);

        $this->assertTrue(
            $this->query
                ->upsert($marseille->setId('not_found'))
                ->script('ctx._source.population += 10000')
                ->execute()
        );

        $this->assertEquals([
            'upsert' => [
                'name' => 'Marseille',
                'zipCode' => '13001',
                'population' => 861635,
                'country' => 'FR',
                'enabled' => true,
            ],
            'script' => 'ctx._source.population += 10000',
        ], $this->query->compile());

        $index->refresh();

        $this->assertEqualsCanonicalizing([
            [
                'name' => 'Marseille',
                'zipCode' => '13001',
                'population' => 861635,
                'country' => 'FR',
                'enabled' => true,
            ],
        ], $this->search()->all());

        $this->assertTrue(
            $this->query
                ->upsert($marseille->setId('not_found'))
                ->script('ctx._source.population += 10000')
                ->execute()
        );
        $index->refresh();

        $this->assertEqualsCanonicalizing([
            [
                'name' => 'Marseille',
                'zipCode' => '13001',
                'population' => 871635,
                'country' => 'FR',
                'enabled' => true,
            ],
        ], $this->search()->all());
    }

    public function test_scriptedUpsert()
    {
        $index = new ElasticsearchIndex(self::getClient(), $this->mapper);

        $this->assertTrue(
            $this->query
                ->id('foo')
                ->scriptedUpsert(<<<'SCRIPT'
                    if (ctx.op == 'create') {
                        ctx._source.population = 1;
                    } else {
                        ctx._source.population += 15000;
                    }
                SCRIPT
                )
                ->execute()
        );

        $this->assertEquals([
            'scripted_upsert' => true,
            'script' => <<<'SCRIPT'
                    if (ctx.op == 'create') {
                        ctx._source.population = 1;
                    } else {
                        ctx._source.population += 15000;
                    }
                SCRIPT
            ,
            'upsert' => new stdClass(),
        ], $this->query->compile());


        $index->refresh();

        $this->assertEqualsCanonicalizing([
            [
                'population' => 1,
            ],
        ], $this->search()->all());

        $this->assertTrue(
            $this->query->execute()
        );
        $index->refresh();

        $this->assertEqualsCanonicalizing([
            [
                'population' => 15001,
            ],
        ], $this->search()->all());
    }

    public function test_missing_id()
    {
        $this->expectException(InvalidQueryException::class);
        $this->expectExceptionMessage('Cannot perform update : Document id is missing. Call ElasticsearchUpdateQuery::id() for define the id.');

        $this->query->document(['foo' => 'bar'])->execute();
    }

    public function search(): ElasticsearchQuery
    {
        return (new ElasticsearchQuery(self::getClient()))->from('test_cities')->map(fn ($data) => $data['_source']);
    }
}
