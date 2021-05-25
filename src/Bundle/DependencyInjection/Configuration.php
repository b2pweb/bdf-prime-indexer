<?php

namespace Bdf\Prime\Indexer\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 * @package Bdf\Prime\Indexer\Bundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('prime_indexer');
        $treeBuilder->getRootNode()
            ->children()
                ->append($this->getElasticsearchNode())
                ->arrayNode('indexes')->scalarPrototype()->end()
            ->end()
        ;

        return $treeBuilder;
    }

    private function getElasticsearchNode(): NodeDefinition
    {
        $root = (new TreeBuilder('elasticsearch'))->getRootNode();

        $root->children()
            ->arrayNode('hosts')->cannotBeEmpty()->scalarPrototype()->end()->end()
            ->arrayNode('connectionParams')->end()
            ->integerNode('retries')->end()
            ->scalarNode('sslCert')->end()
            ->scalarNode('sslKey')->end()
            ->booleanNode('sslVerification')->end()
            ->booleanNode('sniffOnStart')->end()
            ->end()
        ;

        return $root;
    }
}
