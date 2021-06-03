<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Console;

use Bdf\Prime\Indexer\CommandTestCase;
use Bdf\Prime\Indexer\IndexFactory;
use Elasticsearch\Client;
use ElasticsearchTestFiles\City;
use ElasticsearchTestFiles\User;

/**
 * Class DeleteCommandTest
 */
class DeleteCommandTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->factory()->for(User::class)->drop();
        $this->factory()->for(City::class)->drop();

        parent::tearDown();
    }

    /**
     *
     */
    public function test_execute_one()
    {
        $this->factory()->for(User::class)->create();
        $this->factory()->for(City::class)->create();

        $this->execute('elasticsearch:delete', ['indices' => ['test_users']], ['inputs' => ['yes']]);

        $this->assertFalse($this->client()->indices()->existsAlias(['name' => 'test_users']));
        $this->assertTrue($this->client()->indices()->existsAlias(['name' => 'test_cities']));
    }

    /**
     *
     */
    public function test_execute_multiple()
    {
        $this->factory()->for(User::class)->create();
        $this->factory()->for(City::class)->create();

        $this->execute('elasticsearch:delete', ['indices' => ['test_users', 'test_cities']], ['inputs' => ['yes']]);

        $this->assertFalse($this->client()->indices()->existsAlias(['name' => 'test_users']));
        $this->assertFalse($this->client()->indices()->existsAlias(['name' => 'test_cities']));
    }

    /**
     *
     */
    public function test_execute_none()
    {
        $output = $this->execute('elasticsearch:delete', ['indices' => []], ['inputs' => ['yes']]);

        $this->assertContains('Aucun index à supprimer', $output);
    }

    private function client(): Client
    {
        return $this->app->getKernel()->getContainer()->get(Client::class);
    }

    private function factory(): IndexFactory
    {
        return $this->app->getKernel()->getContainer()->get(IndexFactory::class);
    }
}
