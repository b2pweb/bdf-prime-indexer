<?php

namespace Bdf\Prime\Indexer\Console;

use Bdf\Prime\Indexer\CommandTestCase;
use Bdf\Prime\Indexer\CustomEntitiesConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\ClientInterface;
use Bdf\Prime\Indexer\IndexFactory;
use Bdf\Prime\Indexer\ShouldBeIndexedConfigurationInterface;
use Bdf\Prime\Test\TestPack;

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
        $factory = $this->app->getKernel()->getContainer()->get(IndexFactory::class);

        $factory->for(\User::class)->drop();

        parent::tearDown();
    }

    /**
     *
     */
    public function test_execute_without_entities()
    {
        $this->execute('prime:indexer:create', ['entity' => \User::class]);

        $this->assertTrue($this->client()->hasAlias('test_users'));
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

        $this->execute('prime:indexer:create', ['entity' => \User::class]);
        $this->client()->refreshIndex('test_users');

        $this->assertEqualsCanonicalizing($users, $this->factory()->for(\User::class)->query()->all());
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

        $this->execute('prime:indexer:create', ['entity' => \User::class]);
        $this->client()->refreshIndex('test_users');

        $this->assertEqualsCanonicalizing($index->entities(), $this->factory()->for(\User::class)->query()->all());
    }

    /**
     *
     */
    public function test_execute_with_custom_option()
    {
        $this->execute('prime:indexer:create', ['entity' => \User::class, '--options' => '{"useAlias":false}']);

        $this->assertFalse($this->client()->hasAlias('test_users'));
        $this->assertTrue($this->client()->hasIndex('test_users'));
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

        $this->execute('prime:indexer:create', ['entity' => \User::class]);
        $this->client()->refreshIndex('test_users');

        $this->assertEquals([$john], $this->factory()->for(\User::class)->query()->all());
    }

    /**
     *
     */
    public function test_execute_invalid_options()
    {
        $output = $this->execute('prime:indexer:create', ['entity' => \User::class, '--options' => 'invalid']);

        $this->assertFalse($this->client()->hasAlias('test_users'));
        $this->assertStringContainsString('Invalid options given', $output);
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
