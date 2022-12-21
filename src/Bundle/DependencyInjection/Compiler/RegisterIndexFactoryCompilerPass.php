<?php

namespace Bdf\Prime\Indexer\Bundle\DependencyInjection\Compiler;

use Bdf\Prime\Indexer\Bundle\Factory\IndexFactoryInterface;
use Bdf\Prime\Indexer\IndexFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Register custom index factories tagged with "prime.indexer.factory"
 *
 * @see IndexFactory
 */
final class RegisterIndexFactoryCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $factory = $container->findDefinition(IndexFactory::class);
        $factories = [];

        foreach ($container->findTaggedServiceIds('prime.indexer.factory') as $id => $tags) {
            /** @var class-string<IndexFactoryInterface> $factoryClass */
            $factoryClass = $container->findDefinition($id)->getClass();

            $factories[$factoryClass::type()] = new Reference($id);
        }

        $factory->replaceArgument(0, $factories);
    }
}
