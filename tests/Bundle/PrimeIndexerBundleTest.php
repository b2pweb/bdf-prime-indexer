<?php


namespace Bdf\Prime\Indexer\Bundle;

use Bdf\Prime\Indexer\Console\CreateIndexCommand;
use Bdf\Prime\Indexer\Denormalize\DenormalizedIndex;
use Bdf\Prime\Indexer\Elasticsearch\Console\DeleteCommand;
use Bdf\Prime\Indexer\Elasticsearch\Console\ShowCommand;
use Bdf\Prime\Indexer\Elasticsearch\ElasticsearchIndex;
use Bdf\Prime\Indexer\IndexFactory;
use Bdf\Prime\Indexer\TestKernel;
use DenormalizeTestFiles\UserAttributes;
use DenormalizeTestFiles\UserAttributesDenormalizer;
use Elasticsearch\Client;
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
        $this->assertInstanceOf(Client::class, $kernel->getContainer()->get(Client::class));

        $this->assertInstanceOf(ElasticsearchIndex::class, $kernel->getContainer()->get(IndexFactory::class)->for(City::class));
        $this->assertInstanceOf(CityIndex::class, $kernel->getContainer()->get(IndexFactory::class)->for(City::class)->config());
        $this->assertInstanceOf(ElasticsearchIndex::class, $kernel->getContainer()->get(IndexFactory::class)->for(User::class));
        $this->assertInstanceOf(UserIndex::class, $kernel->getContainer()->get(IndexFactory::class)->for(User::class)->config());
        $this->assertInstanceOf(DenormalizedIndex::class, $kernel->getContainer()->get(IndexFactory::class)->for(UserAttributes::class));
        $this->assertInstanceOf(UserAttributesDenormalizer::class, $kernel->getContainer()->get(IndexFactory::class)->for(UserAttributes::class)->config());
    }

    /**
     *
     */
    public function test_commands()
    {
        $kernel = new TestKernel('dev', true);
        $kernel->boot();

        $console = new Application($kernel);

        $this->assertInstanceOf(CreateIndexCommand::class, $console->get('prime:indexer:create'));
        $this->assertInstanceOf(DeleteCommand::class, $console->get('elasticsearch:delete'));
        $this->assertInstanceOf(ShowCommand::class, $console->get('elasticsearch:show'));
    }
}
