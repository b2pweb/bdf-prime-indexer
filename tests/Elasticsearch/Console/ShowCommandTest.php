<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Console;

use Bdf\Prime\Indexer\CommandTestCase;
use Bdf\Prime\Indexer\IndexFactory;
use ElasticsearchTestFiles\User;

/**
 * Class ShowCommandTest
 */
class ShowCommandTest extends CommandTestCase
{
    /**
     *
     */
    protected function tearDown(): void
    {
        $this->factory()->for(User::class)->drop();

        parent::tearDown();
    }

    /**
     *
     */
    public function test_execute()
    {
        $this->factory()->for(User::class)->create();

        $output = $this->execute('elasticsearch:show');

        $this->assertRegExp('# Indices + Types + Aliases +#', $output);
        $this->assertRegExp('# test_users_.{13} + user + test_users +#', $output);
    }

    private function factory(): IndexFactory
    {
        return $this->app->getKernel()->getContainer()->get(IndexFactory::class);
    }
}
