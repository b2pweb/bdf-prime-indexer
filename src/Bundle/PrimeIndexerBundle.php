<?php

namespace Bdf\Prime\Indexer\Bundle;

use Bdf\Prime\Indexer\Resolver\MappingResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class PrimeIndexerBundle
 */
class PrimeIndexerBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container)
            {
                $resolverDefinition = $container->findDefinition(MappingResolver::class);

                foreach ($container->findTaggedServiceIds('prime.indexer.configuration') as $id => $_) {
                    try {
                        $r = new \ReflectionClass($container->findDefinition($id)->getClass());
                        $config = $r->newInstanceWithoutConstructor();

                        $resolverDefinition->addMethodCall('register', [$id, $config->entity()]);
                    } catch (\Exception $e) {
                        // Ignore
                    }
                }
            }
        });
    }
}
