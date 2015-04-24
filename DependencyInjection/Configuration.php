<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\DependencyInjection;

use Psr\Log\LogLevel;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * This is the class that validates and merges configuration from app/config files.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ongr_elasticsearch');

        $rootNode
            ->children()
                ->scalarNode('document_dir')
                    ->info("Sets directory name from which documents will be loaded from bundles.'Document' by default")
                    ->defaultValue('Document')
                ->end()
                ->append($this->getConnectionsNode())
                ->append($this->getManagersNode())
            ->end();

        return $treeBuilder;
    }

    /**
     * Connections configuration node.
     *
     * @return \Symfony\Component\Config\Definition\Builder\NodeDefinition
     *
     * @throws InvalidConfigurationException
     */
    private function getConnectionsNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('connections');

        $node
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->info('Defines connections to indexes and its settings.')
            ->prototype('array')
                ->children()
                    ->arrayNode('hosts')
                        ->info('Defines hosts to connect to.')
                        ->requiresAtLeastOneElement()
                        ->defaultValue(['127.0.0.1:9200'])
                        ->prototype('scalar')
                            ->beforeNormalization()
                                ->ifArray()
                                ->then(
                                    function ($value) {
                                        if (!array_key_exists('host', $value)) {
                                            throw new InvalidConfigurationException(
                                                'Host must be configured under hosts configuration tree.'
                                            );
                                        }

                                        return $value['host'];
                                    }
                                )
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('auth')
                        ->info('holds information for http authentication.')
                        ->children()
                            ->scalarNode('username')
                                ->isRequired()
                                ->example('john')
                            ->end()
                            ->scalarNode('password')
                                ->isRequired()
                                ->example('mytopsecretpassword')
                            ->end()
                            ->scalarNode('option')
                                ->defaultValue('Basic')
                                ->info('authentication type')
                            ->end()
                        ->end()
                    ->end()
                    ->scalarNode('index_name')
                        ->isRequired()
                        ->info('Sets index name for connection.')
                    ->end()
                    ->arrayNode('settings')
                        ->defaultValue([])
                        ->info('Sets index settings for connection.')
                        ->prototype('variable')->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    /**
     * Managers configuration node.
     *
     * @return \Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    private function getManagersNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('managers');

        $node
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->info('Maps managers to connections and bundles')
            ->prototype('array')
                ->children()
                    ->scalarNode('connection')
                        ->isRequired()
                        ->info('Sets connection for manager.')
                    ->end()
                    ->arrayNode('debug')
                        ->info('Enables logging')
                        ->addDefaultsIfNotSet()
                        ->beforeNormalization()
                            ->ifTrue(
                                function ($v) {
                                    return is_bool($v);
                                }
                            )
                            ->then(
                                function ($v) {
                                    return ['enabled' => $v];
                                }
                            )
                        ->end()
                        ->children()
                            ->booleanNode('enabled')
                                ->info('enables logging')
                                ->defaultFalse()
                            ->end()
                            ->scalarNode('level')
                                ->info('Sets psr logging level')
                                ->defaultValue(LogLevel::WARNING)
                                ->validate()
                                ->ifNotInArray((new \ReflectionClass('Psr\Log\LogLevel'))->getConstants())
                                    ->thenInvalid('Invalid Psr log level.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->booleanNode('readonly')
                        ->info('Sets manager to read only state.')
                        ->defaultFalse()
                    ->end()
                    ->arrayNode('mappings')
                        ->info('Maps manager to bundles. f.e. AcmeDemoBundle')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }
}
