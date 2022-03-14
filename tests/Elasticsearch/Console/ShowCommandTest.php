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

        $this->assertMatchesRegularExpression('# Indices +| Properties +| Aliases +#', $output);
        $this->assertMatchesRegularExpression('# test_users_.{13} +| .* +| test_users +#', $output);
        $this->assertStringContainsString('email: text', $output);
        $this->assertStringContainsString('login: keyword', $output);
        $this->assertStringContainsString('name: text', $output);
        $this->assertStringContainsString('password: keyword', $output);
        $this->assertStringContainsString('roles: text', $output);
    }

    private function factory(): IndexFactory
    {
        return $this->app->getKernel()->getContainer()->get(IndexFactory::class);
    }
}
