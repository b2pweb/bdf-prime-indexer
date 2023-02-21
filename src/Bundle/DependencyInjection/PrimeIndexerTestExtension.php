<?php

namespace Bdf\Prime\Indexer\Bundle\DependencyInjection;

use Bdf\Prime\Indexer\Test\TestingIndexer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

final class PrimeIndexerTestExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $container->register(TestingIndexer::class)
            ->setArguments([new Reference('service_container'), false])
            ->setPublic(true)
        ;
    }
}
