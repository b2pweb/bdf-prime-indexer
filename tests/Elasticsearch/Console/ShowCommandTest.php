<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Console;

use Bdf\Prime\Indexer\CommandTestCase;
use Bdf\Prime\Indexer\IndexFactory;
use Bdf\Prime\Indexer\IndexTestCase;

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
        $this->factory()->for(\User::class)->drop();

        parent::tearDown();
    }

    /**
     *
     */
    public function test_execute()
    {
        $this->factory()->for(\User::class)->create();

        $output = $this->execute('elasticsearch:show');

        $this->assertRegExp('# Indices + Types + Aliases +#', $output);

        if (IndexTestCase::minimalElasticsearchVersion('7.0')) {
            $this->assertRegExp('# test_users_.{13} + {6} + test_users +#', $output);
        } else {
            $this->assertRegExp('# test_users_.{13} + user + test_users +#', $output);
        }
    }

    private function factory(): IndexFactory
    {
        return $this->app->getKernel()->getContainer()->get(IndexFactory::class);
    }
}
