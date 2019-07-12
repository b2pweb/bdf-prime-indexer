<?php

namespace Bdf\Prime\Indexer;

use Bdf\Bus\BusServiceProvider;
use Bdf\Prime\Indexer\Sync\RepositorySubscriber;
use Bdf\Prime\PrimeServiceProvider;
use Bdf\Prime\ServiceLocator;
use Bdf\Web\Application;
use Bdf\Web\Providers\BootableProviderInterface;
use Bdf\Web\Providers\ServiceProviderInterface;

/**
 * Configure prime repository to index synchronization
 *
 * Prime and indexer must be configured for enable synchronization
 *
 * @see PrimeIndexerServiceProvider
 * @see PrimeServiceProvider
 * @see BusServiceProvider
 */
class PrimeIndexerSynchronizationProvider implements ServiceProviderInterface, BootableProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(Application $app)
    {
        if (!$app->has('prime.index.bus')) {
            $app->set('prime.index.bus', function (Application $app) {
                return $app->get('bus.dispatcher');
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        /** @var ServiceLocator $prime */
        $prime = $app['prime'];
        $dispatcher = $app['prime.index.bus'];

        foreach ($app->get('prime.indexes') as $index => $config) {
            if ($repository = $prime->repository($index)) {
                (new RepositorySubscriber($dispatcher, $index, $config))->subscribe($repository);
            }
        }
    }
}
