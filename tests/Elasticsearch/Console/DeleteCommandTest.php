<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Console;

use Bdf\Prime\Indexer\CommandTestCase;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\ClientInterface;
use Bdf\Prime\Indexer\IndexFactory;

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
        $this->factory()->for(\User::class)->drop();
        $this->factory()->for(\City::class)->drop();

        parent::tearDown();
    }

    /**
     *
     */
    public function test_execute_one()
    {
        $this->factory()->for(\User::class)->create();
        $this->factory()->for(\City::class)->create();

        $this->execute('elasticsearch:delete', ['indices' => ['test_users']], ['inputs' => ['yes']]);

        $this->assertFalse($this->client()->hasAlias('test_users'));
        $this->assertTrue($this->client()->hasAlias('test_cities'));
    }

    /**
     *
     */
    public function test_execute_multiple()
    {
        $this->factory()->for(\User::class)->create();
        $this->factory()->for(\City::class)->create();

        $this->execute('elasticsearch:delete', ['indices' => ['test_users', 'test_cities']], ['inputs' => ['yes']]);

        $this->assertFalse($this->client()->hasAlias('test_users'));
        $this->assertFalse($this->client()->hasAlias('test_cities'));
    }

    /**
     *
     */
    public function test_execute_all()
    {
        $this->factory()->for(\User::class)->create();
        $this->factory()->for(\City::class)->create();

        $this->execute('elasticsearch:delete', ['--all' => true], ['inputs' => ['yes']]);

        $this->assertFalse($this->client()->hasAlias('test_users'));
        $this->assertFalse($this->client()->hasAlias('test_cities'));
    }

    /**
     *
     */
    public function test_execute_none()
    {
        $output = $this->execute('elasticsearch:delete', ['indices' => []], ['inputs' => ['yes']]);

        $this->assertStringContainsString('Aucun index à supprimer', $output);
    }

    private function client(): ClientInterface
    {
        return $this->app->getKernel()->getContainer()->get(ClientInterface::class);
    }

    private function factory(): IndexFactory
    {
        return $this->app->getKernel()->getContainer()->get(IndexFactory::class);
    }
}
