<?php

namespace Bdf\Prime\Indexer;

use Bdf\Prime\Indexer\Bundle\PrimeIndexerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class TestKernel extends \Symfony\Component\HttpKernel\Kernel
{
    use \Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;

    /**
     * @param ContainerConfigurator|ContainerBuilder $container
     * @param LoaderInterface|null $loader
     */
    public function configureContainer($container, $loader = null): void
    {
        if ($loader !== null) {
            $loader->load(__DIR__.'/conf.yaml');
        } else {
            $container->import(__DIR__ . '/conf.yaml');
        }
    }

    public function registerBundles(): iterable
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Bdf\PrimeBundle\PrimeBundle(),
//            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new PrimeIndexerBundle(),
        ];
    }

    public function configureRoutes($routes): void
    {
    }
}
