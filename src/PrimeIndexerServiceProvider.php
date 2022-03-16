<?php

namespace Bdf\Prime\Indexer;

use Bdf\Console\Console;
use Bdf\Prime\Entity\Instantiator\Instantiator;
use Bdf\Prime\Indexer\Console\CreateIndexCommand;
use Bdf\Prime\Indexer\Elasticsearch\Console\DeleteCommand;
use Bdf\Prime\Indexer\Elasticsearch\Console\ShowCommand;
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
        $this->configureElasticsearchCommands($app);

        $app->set(Client::class, function (Application $app) {
            return ClientBuilder::fromConfig($app->config('elasticsearch'));
        });

        $app->set('prime.index.factories', function (Application $app) {
            return [
                ElasticsearchIndexConfigurationInterface::class => function (ElasticsearchIndexConfigurationInterface $configuration) use ($app) {
                    return new ElasticsearchIndex(
                        $app->get(Client::class),
                        new ElasticsearchMapper(
                            $configuration,
                            $app->has('prime-instantiator')
                                ? $app->get('prime-instantiator')
                                : new Instantiator()
                        )
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

    private function configureElasticsearchCommands(Application $app): void
    {
        $app->factory(ShowCommand::class, function (Application $app) {
            return new ShowCommand($app->get(Client::class), $app->config()->all());
        });
        $app->factory(DeleteCommand::class, function (Application $app) {
            return new DeleteCommand($app->get(Client::class), $app->config()->all());
        });
    }

    /**
     * {@inheritdoc}
     */
    public function provideCommands(Console $console)
    {
        $console->lazy(CreateIndexCommand::class);

        $console->lazy(ShowCommand::class);
        $console->lazy(DeleteCommand::class);
    }
}
