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
            ->append($this->getAnalysisNode())
            ->append($this->getConnectionsNode())
            ->append($this->getManagersNode())
            ->end();

        return $treeBuilder;
    }

    /**
     * Analysis configuration node.
     *
     * @return \Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    private function getAnalysisNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('analysis');

        $node
            ->info('Defines analyzers, tokenizers and filters')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('tokenizer')
                    ->defaultValue([])
                    ->prototype('variable')->end()
                ->end()
                ->arrayNode('filter')
                    ->defaultValue([])
                    ->prototype('variable')->end()
                ->end()
                ->arrayNode('analyzer')
                    ->defaultValue([])
                    ->prototype('variable')->end()
                ->end()
            ->end();

        return $node;
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
                        ->isRequired()
                        ->defaultValue(['127.0.0.1:9200'])
                        ->prototype('scalar')
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
                    ->arrayNode('analysis')
                        ->addDefaultsIfNotSet()
                        ->info('Sets index analysis settings for connection.')
                        ->children()
                            ->arrayNode('tokenizer')->prototype('scalar')->defaultValue([])->end()->end()
                            ->arrayNode('filter')->prototype('scalar')->defaultValue([])->end()->end()
                            ->arrayNode('analyzer')->prototype('scalar')->defaultValue([])->end()->end()
                        ->end()
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
                    ->booleanNode('profiler')
                        ->info('Enables elasticsearch profiler in the sf web profiler toolbar.')
                        ->defaultFalse()
                    ->end()
                    ->arrayNode('logger')
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
                            ->scalarNode('log_file_name')
                                ->info('Log filename, by default it is manager name')
                                ->defaultValue(null)
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
