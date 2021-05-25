<?php

namespace Bdf\Prime\Indexer;

use Bdf\Prime\Indexer\Bundle\PrimeIndexerBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class TestKernel extends \Symfony\Component\HttpKernel\Kernel
{
    use \Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;

    /**
     * @param ContainerConfigurator|ContainerBuilder $container
     */
    public function configureContainer($container): void
    {
        $container->import(__DIR__.'/conf.yaml');
    }

    public function registerBundles()
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Bdf\PrimeBundle\PrimeBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new PrimeIndexerBundle(),
        ];
    }

    public function configureRoutes(\Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator $routes): void
    {
    }
}
