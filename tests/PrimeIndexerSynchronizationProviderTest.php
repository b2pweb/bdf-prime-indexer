<?php

namespace Bdf\Prime\Indexer;

use Bdf\Bus\BusServiceProvider;
use Bdf\Config\Config;
use Bdf\Prime\Events;
use Bdf\Prime\Indexer\Sync\RepositorySubscriber;
use Bdf\Prime\PrimeServiceProvider;
use Bdf\Web\Application;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Class PrimeIndexerSynchronizationProviderTest
 */
class PrimeIndexerSynchronizationProviderTest extends TestCase
{
    /**
     * @var Application
     */
    private $app;

    protected function setUp(): void
    {
        parent::setUp();

        if (!class_exists(Application::class)) {
            $this->markTestSkipped();
        }

        $this->app = new Application([
            'config' => new Config([
                'elasticsearch' => ['hosts' => ['127.0.0.1:9222']]
            ]),
            'prime.indexes' => [
                \City::class => new \CityIndex(),
                \User::class => new \UserIndex(),
            ],
            'logger' => new NullLogger()
        ]);
        $this->app->register(new BusServiceProvider());
        $this->app->register(new PrimeServiceProvider());
        $this->app->register(new PrimeIndexerServiceProvider());

        $this->app['prime']->connections()->addConnection('test', ['adapter' => 'sqlite', 'memory' => true]);
    }

    /**
     *
     */
    public function test_boot()
    {
        $this->app->register(new PrimeIndexerSynchronizationProvider());
        $this->app->boot();

        $this->assertInstanceOf(RepositorySubscriber::class, $this->app['prime']->repository(\User::class)->listeners(Events::POST_UPDATE)[0][0]);
        $this->assertInstanceOf(RepositorySubscriber::class, $this->app['prime']->repository(\User::class)->listeners(Events::POST_INSERT)[0][0]);
        $this->assertInstanceOf(RepositorySubscriber::class, $this->app['prime']->repository(\User::class)->listeners(Events::POST_DELETE)[0][0]);

        $this->assertInstanceOf(RepositorySubscriber::class, $this->app['prime']->repository(\City::class)->listeners(Events::POST_UPDATE)[0][0]);
        $this->assertInstanceOf(RepositorySubscriber::class, $this->app['prime']->repository(\City::class)->listeners(Events::POST_INSERT)[0][0]);
        $this->assertInstanceOf(RepositorySubscriber::class, $this->app['prime']->repository(\City::class)->listeners(Events::POST_DELETE)[0][0]);
    }
}
