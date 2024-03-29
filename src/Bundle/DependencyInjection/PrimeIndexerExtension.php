<?php

namespace Bdf\Prime\Indexer\Bundle\DependencyInjection;

use Bdf\Prime\Indexer\Bundle\Factory\IndexFactoryInterface;
use Bdf\Prime\Indexer\IndexConfigurationInterface;
use Bdf\Prime\Indexer\IndexFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class PrimeIndexerExtension
 */
class PrimeIndexerExtension extends Extension
{
    /**
     * {@inheritdoc}
     *
     * @psalm-suppress PossiblyNullArgument
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('prime_indexer.yaml');

        $container->setParameter('prime.indexer.configuration', $config);
        $container->setParameter('prime.indexer.configuration.elasticsearch', $config['elasticsearch'] ?? []);

        $container->registerForAutoconfiguration(IndexFactoryInterface::class)
            ->setPublic(true)
            ->addTag('prime.indexer.factory')
        ;

        $container->registerForAutoconfiguration(IndexConfigurationInterface::class)
            ->setPublic(true)
            ->addTag('prime.indexer.configuration')
        ;

        $this->configureIndexes($config, $container);
    }

    private function configureIndexes(array $config, ContainerBuilder $container): void
    {
        $factory = $container->findDefinition(IndexFactory::class);

        foreach ($config['indexes'] as $entity => $index) {
            $definition = $container->hasDefinition($index)
                ? $container->findDefinition($index)
                : $container->register($index, $index)->setAutowired(true)
            ;

            $factory->addMethodCall('register', [$entity, $definition]);
        }
    }
}
