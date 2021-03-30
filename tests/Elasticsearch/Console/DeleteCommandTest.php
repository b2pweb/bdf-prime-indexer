<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Console;

use Bdf\Config\Config;
use Bdf\Prime\Indexer\CommandTestCase;
use Bdf\Prime\Indexer\IndexFactory;
use Bdf\Prime\Indexer\PrimeIndexerServiceProvider;
use Bdf\Prime\PrimeServiceProvider;
use Bdf\Web\Application;
use Elasticsearch\Client;

/**
 * Class DeleteCommandTest
 */
class DeleteCommandTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->di = new Application([
            'config' => new Config([
                'elasticsearch' => ['hosts' => ['127.0.0.1:9222']]
            ]),
            'prime.indexes' => [
                \User::class => new \UserIndex(),
                \City::class => new \CityIndex(),
            ]
        ]);
        $this->di->register(new PrimeServiceProvider());
        $this->di->register(new PrimeIndexerServiceProvider());
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->factory()->for(\User::class)->drop();
        $this->factory()->for(\City::class)->drop();
    }

    /**
     *
     */
    public function test_execute_one()
    {
        $this->factory()->for(\User::class)->create();
        $this->factory()->for(\City::class)->create();

        $this->execute(DeleteCommand::class, ['indices' => ['test_users']], ['inputs' => ['yes']]);

        $this->assertFalse($this->client()->indices()->existsAlias(['name' => 'test_users']));
        $this->assertTrue($this->client()->indices()->existsAlias(['name' => 'test_cities']));
    }

    /**
     *
     */
    public function test_execute_multiple()
    {
        $this->factory()->for(\User::class)->create();
        $this->factory()->for(\City::class)->create();

        $this->execute(DeleteCommand::class, ['indices' => ['test_users', 'test_cities']], ['inputs' => ['yes']]);

        $this->assertFalse($this->client()->indices()->existsAlias(['name' => 'test_users']));
        $this->assertFalse($this->client()->indices()->existsAlias(['name' => 'test_cities']));
    }

    /**
     *
     */
    public function test_execute_none()
    {
        $output = $this->execute(DeleteCommand::class, ['indices' => []], ['inputs' => ['yes']]);

        $this->assertContains('Aucun index à supprimer', $output);
    }

    private function client(): Client
    {
        return $this->di[Client::class];
    }

    private function factory(): IndexFactory
    {
        return $this->di[IndexFactory::class];
    }
}
