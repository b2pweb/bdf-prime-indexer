<?php

namespace Bdf\Prime\Indexer\Console;

use Bdf\Config\Config;
use Bdf\Prime\Indexer\CommandTestCase;
use Bdf\Prime\Indexer\CustomEntitiesConfigurationInterface;
use Bdf\Prime\Indexer\IndexFactory;
use Bdf\Prime\Indexer\PrimeIndexerServiceProvider;
use Bdf\Prime\Indexer\ShouldBeIndexedConfigurationInterface;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeServiceProvider;
use Bdf\Prime\Test\TestPack;
use Bdf\Web\Application;
use Elasticsearch\Client;
use Psr\Log\NullLogger;

/**
 * Class CreateIndexCommandTest
 */
class CreateIndexCommandTest extends CommandTestCase
{
    /**
     * @var TestPack
     */
    private $testPack;

    protected function setUp(): void
    {
        parent::setUp();

        $this->di = new Application([
            'config' => new Config([
                'elasticsearch' => ['hosts' => ['127.0.0.1:9222']]
            ]),
            'prime.indexes' => [
                \User::class => new \UserIndex(),
            ],
            'logger' => new NullLogger()
        ]);
        $this->di->register(new PrimeServiceProvider());
        $this->di->register(new PrimeIndexerServiceProvider());

        $this->di['prime']->connections()->addConnection('test', ['adapter' => 'sqlite', 'memory' => true]);
        Prime::configure($this->di['prime']);

        $this->testPack = new TestPack();
        $this->testPack
            ->declareEntity(\User::class)
            ->initialize()
        ;
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->testPack->destroy();

        /** @var IndexFactory $factory */
        $factory = $this->di[IndexFactory::class];

        $factory->for(\User::class)->drop();
    }

    /**
     *
     */
    public function test_execute_without_entities()
    {
        $this->execute(CreateIndexCommand::class, ['entity' => \User::class]);

        $this->assertTrue($this->client()->indices()->existsAlias(['name' => 'test_users']));
    }

    /**
     *
     */
    public function test_execute_with_entities()
    {
        $this->testPack->nonPersist($users = [
            new \User([
                'name' => 'John',
                'email' => 'john.doe@example.com',
                'password' => 'my secure password',
                'roles' => ['3', '5'],
            ]),
            new \User([
                'name' => 'Bob',
                'email' => 'bob@example.com',
                'password' => 'my secure password',
                'roles' => ['4'],
            ]),
        ]);

        $this->execute(CreateIndexCommand::class, ['entity' => \User::class]);
        $this->client()->indices()->refresh();

        $this->assertEquals($users, $this->factory()->for(\User::class)->query()->all(), '', 0, 1, true);
    }

    /**
     *
     */
    public function test_execute_with_custom_entities_method()
    {
        $index = new class extends \UserIndex implements CustomEntitiesConfigurationInterface {
            public function entities(): iterable
            {
                return [
                    new \User([
                        'name' => 'John',
                        'email' => 'john.doe@example.com',
                        'password' => 'my secure password',
                        'roles' => ['3', '5'],
                    ]),
                    new \User([
                        'name' => 'Bob',
                        'email' => 'bob@example.com',
                        'password' => 'my secure password',
                        'roles' => ['4'],
                    ]),
                ];
            }
        };

        $this->factory()->register(\User::class, $index);

        $this->execute(CreateIndexCommand::class, ['entity' => \User::class]);
        $this->client()->indices()->refresh();

        $this->assertEquals($index->entities(), $this->factory()->for(\User::class)->query()->all(), '', 0, 1, true);
    }

    /**
     *
     */
    public function test_execute_with_custom_option()
    {
        $this->execute(CreateIndexCommand::class, ['entity' => \User::class, '--options' => '{"useAlias":false}']);

        $this->assertFalse($this->client()->indices()->existsAlias(['name' => 'test_users']));
        $this->assertTrue($this->client()->indices()->exists(['index' => 'test_users']));
    }

    /**
     *
     */
    public function test_execute_with_with_should_be_indexed()
    {
        $this->testPack->nonPersist([
            $john = new \User([
                'name' => 'John',
                'email' => 'john.doe@example.com',
                'password' => 'my secure password',
                'roles' => ['3', '5'],
            ]),
            new \User([
                'name' => 'Bob',
                'email' => 'bob@example.com',
                'password' => 'my secure password',
                'roles' => ['4'],
            ]),
        ]);

        $index = new class extends \UserIndex implements ShouldBeIndexedConfigurationInterface {
            public function shouldBeIndexed($entity): bool
            {
                return in_array('3', $entity->roles());
            }
        };

        $this->factory()->register(\User::class, $index);

        $this->execute(CreateIndexCommand::class, ['entity' => \User::class]);
        $this->client()->indices()->refresh();

        $this->assertEquals([$john], $this->factory()->for(\User::class)->query()->all());
    }

    /**
     *
     */
    public function test_execute_invalid_options()
    {
        $output = $this->execute(CreateIndexCommand::class, ['entity' => \User::class, '--options' => 'invalid']);

        $this->assertFalse($this->client()->indices()->existsAlias(['name' => 'test_users']));
        $this->assertContains('Invalid options given', $output);
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
