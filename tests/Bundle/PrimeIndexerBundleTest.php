<?php


namespace Bdf\Prime\Indexer\Bundle;

use Bdf\Prime\Indexer\Console\CreateIndexCommand;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\ClientInterface;
use Bdf\Prime\Indexer\Elasticsearch\Console\DeleteCommand;
use Bdf\Prime\Indexer\Elasticsearch\Console\ShowCommand;
use Bdf\Prime\Indexer\Elasticsearch\ElasticsearchIndex;
use Bdf\Prime\Indexer\IndexFactory;
use Bdf\Prime\Indexer\TestKernel;
use ElasticsearchTestFiles\City;
use ElasticsearchTestFiles\CityIndex;
use ElasticsearchTestFiles\User;
use ElasticsearchTestFiles\UserIndex;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;

/**
 * Class PrimeIndexerBundleTest
 * @package Bdf\Prime\Indexer\Bundle
 */
class PrimeIndexerBundleTest extends TestCase
{
    /**
     *
     */
    public function test_instances()
    {
        $kernel = new TestKernel('dev', true);
        $kernel->boot();

        $this->assertInstanceOf(IndexFactory::class, $kernel->getContainer()->get(IndexFactory::class));
        $this->assertInstanceOf(ClientInterface::class, $kernel->getContainer()->get(ClientInterface::class));
        $this->assertSame($kernel->getContainer()->get(ClientInterface::class)->getInternalClient(), $kernel->getContainer()->get('Elasticsearch\Client'));

        $this->assertInstanceOf(ElasticsearchIndex::class, $kernel->getContainer()->get(IndexFactory::class)->for(City::class));
        $this->assertInstanceOf(CityIndex::class, $kernel->getContainer()->get(IndexFactory::class)->for(City::class)->config());
        $this->assertInstanceOf(ElasticsearchIndex::class, $kernel->getContainer()->get(IndexFactory::class)->for(User::class));
        $this->assertInstanceOf(UserIndex::class, $kernel->getContainer()->get(IndexFactory::class)->for(User::class)->config());
    }

    /**
     *
     */
    public function test_commands()
    {
        $kernel = new TestKernel('dev', true);
        $kernel->boot();

        $console = new Application($kernel);

        if (PHP_MAJOR_VERSION < 8) {
            $this->assertInstanceOf(CreateIndexCommand::class, $console->get('prime:indexer:create'));
            $this->assertInstanceOf(DeleteCommand::class, $console->get('elasticsearch:delete'));
            $this->assertInstanceOf(ShowCommand::class, $console->get('elasticsearch:show'));
        } else {
            $this->assertInstanceOf(CreateIndexCommand::class, $console->get('prime:indexer:create')->getCommand());
            $this->assertInstanceOf(DeleteCommand::class, $console->get('elasticsearch:delete')->getCommand());
            $this->assertInstanceOf(ShowCommand::class, $console->get('elasticsearch:show')->getCommand());
        }
    }
}
