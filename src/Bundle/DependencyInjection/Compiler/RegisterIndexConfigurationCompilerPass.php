<?php

namespace Bdf\Prime\Indexer\Bundle\DependencyInjection\Compiler;

use Bdf\Prime\Indexer\Resolver\MappingResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Locate all services with tag "prime.indexer.configuration" and register into the mapping resolver
 *
 * @see MappingResolver::register()
 */
final class RegisterIndexConfigurationCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $resolverDefinition = $container->findDefinition(MappingResolver::class);

        foreach ($container->findTaggedServiceIds('prime.indexer.configuration') as $id => $_) {
            try {
                $definition = $container->findDefinition($id);
                $definition->setPublic(true);

                $r = new \ReflectionClass($definition->getClass());
                $config = $r->newInstanceWithoutConstructor();

                $resolverDefinition->addMethodCall('register', [$id, $config->entity()]);
            } catch (\Exception $e) {
                // Ignore
            }
        }
    }
}
