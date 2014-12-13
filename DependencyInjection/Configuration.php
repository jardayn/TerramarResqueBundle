<?php

namespace Terramar\Bundle\ResqueBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('resque');

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('prefix')
                    ->defaultNull()
                    ->end()
                ->scalarNode('class')
                    ->defaultValue('Terramar\Bundle\ResqueBundle\Resque')
                    ->cannotBeEmpty()
                    ->info('Set the resque class')
                ->end()
                ->arrayNode('redis')
                    ->info('Redis configuration')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('host')
                            ->defaultValue('localhost')
                            ->cannotBeEmpty()
                            ->info('The redis hostname')
                        ->end()
                        ->scalarNode('port')
                            ->defaultValue(6379)
                            ->cannotBeEmpty()
                            ->info('The redis port')
                        ->end()
                        ->scalarNode('database')
                            ->defaultValue(0)
                            ->info('The redis database')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
