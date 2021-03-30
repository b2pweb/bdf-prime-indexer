<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Console;

use Bdf\Config\Config;
use Bdf\Prime\Indexer\CommandTestCase;
use Bdf\Prime\Indexer\IndexFactory;
use Bdf\Prime\Indexer\PrimeIndexerServiceProvider;
use Bdf\Prime\PrimeServiceProvider;
use Bdf\Web\Application;

/**
 * Class ShowCommandTest
 */
class ShowCommandTest extends CommandTestCase
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
    }

    /**
     *
     */
    public function test_execute()
    {
        $this->factory()->for(\User::class)->create();

        $output = $this->execute(ShowCommand::class);

        $this->assertRegExp('# Indices + Types + Aliases +#', $output);
        $this->assertRegExp('# test_users_.{13} + user + test_users +#', $output);
    }

    private function factory(): IndexFactory
    {
        return $this->di[IndexFactory::class];
    }
}
