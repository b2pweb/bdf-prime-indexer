<?php

namespace Bdf\Prime\Indexer\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use function array_filter;

/**
 * Class Configuration
 * @package Bdf\Prime\Indexer\Bundle\DependencyInjection
 *
 * @psalm-suppress PossiblyUndefinedMethod
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
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

        // Use validate() instead of beforeNormalization() to remove empty values
        // because empty nodes are added after the normalization
        $root->validate()->always(fn (array $config) => array_filter($config, fn ($value) => $value !== '' && $value !== null && $value !== []))->end();

        $root->children()
                ->arrayNode('hosts')->cannotBeEmpty()->scalarPrototype()->end()->end()
                ->arrayNode('connectionParams')->variablePrototype()->end()->end()
                ->integerNode('retries')->end()
                ->scalarNode('sslCert')->end()
                ->scalarNode('sslKey')->end()
                ->booleanNode('sslVerification')->end()
                ->booleanNode('sniffOnStart')->end()
                ->arrayNode('basicAuthentication')
                    ->scalarPrototype()->end()
                    // Remove http basic auth if the value is ['', ''] (i.e. empty username and password)
                    ->beforeNormalization()->ifTrue(fn (array $v) => array_filter($v) === [])->thenEmptyArray()->end()
                ->end()
            ->end()
        ;

        return $root;
    }
}
