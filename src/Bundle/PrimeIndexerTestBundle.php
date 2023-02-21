<?php

namespace Bdf\Prime\Indexer\Bundle;

use Bdf\Prime\Indexer\Test\TestingIndexer;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class PrimeIndexerTestBundle extends Bundle
{
    public function boot()
    {
        $this->container->get(TestingIndexer::class)->init();
    }

    public function shutdown()
    {
        $this->container->get(TestingIndexer::class)->destroy();
    }
}
