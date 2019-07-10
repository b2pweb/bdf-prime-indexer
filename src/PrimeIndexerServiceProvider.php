<?php

namespace Bdf\Prime\Indexer;

use Bdf\Console\Console;
use Bdf\Prime\Indexer\Console\CreateIndexCommand;
use Bdf\Prime\Indexer\Elasticsearch\Console\ShellCommand;
use Bdf\Prime\Indexer\Elasticsearch\ElasticsearchIndex;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapper;
use Bdf\Web\Application;
use Bdf\Web\Providers\CommandProviderInterface;
use Bdf\Web\Providers\ServiceProviderInterface;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

/**
 * Register prime indexer
 *
 * Note: PrimeServiceProvider must be registered
 *
 * Configuration items :
 * - elasticsearch.host[] : Array of hosts for the elasticsearch client
 *
 * Container items :
 * - prime.indexes : Configuration array for register indexes
 */
class PrimeIndexerServiceProvider implements ServiceProviderInterface, CommandProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(Application $app)
    {
        $app->set(Client::class, function (Application $app) {
            return ClientBuilder::fromConfig($app->config('elasticsearch'));
        });

        $app->set('prime.index.factories', function (Application $app) {
            return [
                ElasticsearchIndexConfigurationInterface::class => function (ElasticsearchIndexConfigurationInterface $configuration) use($app) {
                    return new ElasticsearchIndex(
                        $app->get(Client::class),
                        new ElasticsearchMapper($configuration, $app->get('prime-instantiator'))
                    );
                },
            ];
        });

        $app->set(IndexFactory::class, function (Application $app) {
            return new IndexFactory(
                $app->get('prime.index.factories'),
                $app->has('prime.indexes') ? $app->get('prime.indexes') : []
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function provideCommands(Console $console)
    {
        $console->lazy(CreateIndexCommand::class);
        $console->lazy(ShellCommand::class);
    }
}
