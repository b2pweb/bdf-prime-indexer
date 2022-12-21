<?php

namespace Bdf\Prime\Indexer\Bundle;

use Bdf\Prime\Indexer\Bundle\DependencyInjection\Compiler\RegisterIndexConfigurationCompilerPass;
use Bdf\Prime\Indexer\Bundle\DependencyInjection\Compiler\RegisterIndexFactoryCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class PrimeIndexerBundle
 */
class PrimeIndexerBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RegisterIndexFactoryCompilerPass());
        $container->addCompilerPass(new RegisterIndexConfigurationCompilerPass());
    }
}
