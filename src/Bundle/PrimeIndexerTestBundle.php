<?php

namespace Bdf\Prime\Indexer\Bundle;

use Bdf\Prime\Indexer\Test\TestingIndexer;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class PrimeIndexerTestBundle extends Bundle
{
    public function boot(): void
    {
        if (!$container = $this->container) {
            return;
        }

        /** @var TestingIndexer $testingIndexer */
        $testingIndexer = $container->get(TestingIndexer::class);
        $testingIndexer->init();
    }

    public function shutdown(): void
    {
        if (!$container = $this->container) {
            return;
        }

        /** @var TestingIndexer $testingIndexer */
        $testingIndexer = $container->get(TestingIndexer::class);
        $testingIndexer->destroy();
    }
}
