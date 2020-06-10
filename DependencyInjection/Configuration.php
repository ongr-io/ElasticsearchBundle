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
        $treeBuilder = new TreeBuilder('ongr_elasticsearch');

        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            $rootNode = $treeBuilder->root('fos_rest');
        }

        $rootNode
            ->children()
            ->booleanNode('cache')
                ->info(
                    'Enables cache handler to store metadata and other data to the cache. '.
                    'By default it is enabled in prod environment and disabled in dev.'
                )
            ->end()
            ->booleanNode('profiler')
                ->info(
                    'Enables ElasticsearchBundle query profiler. Default value is kernel.debug parameter. '.
                    'If profiler is disabled the tracer service will be disabled as well.'
                )
            ->end()
            ->append($this->getAnalysisNode())
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
        $treeBuilder = new TreeBuilder('analysis');

        if (method_exists($treeBuilder, 'getRootNode')) {
            $node = $treeBuilder->getRootNode();
        } else {
            $node = $treeBuilder->root('analysis');
        }

        $node
            ->info('Defines analyzers, normalizers, tokenizers and filters')
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
                ->arrayNode('normalizer')
                    ->defaultValue([])
                    ->prototype('variable')->end()
                ->end()
                ->arrayNode('char_filter')
                    ->defaultValue([])
                    ->prototype('variable')->end()
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
        $treeBuilder = new TreeBuilder('managers');

        if (method_exists($treeBuilder, 'getRootNode')) {
            $node = $treeBuilder->getRootNode();
        } else {
            $node = $treeBuilder->root('managers');
        }

        $node
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->info('Maps managers to connections and bundles')
            ->prototype('array')
                ->children()
                    ->arrayNode('index')
                        ->children()
                            ->scalarNode('index_name')
                                ->isRequired()
                                ->info('Sets index name for connection.')
                            ->end()
                            ->arrayNode('hosts')
                                ->info('Defines hosts to connect to.')
                                ->defaultValue(['127.0.0.1:9200'])
                                ->prototype('scalar')
                                ->end()
                            ->end()
                            ->arrayNode('settings')
                                ->defaultValue(
                                    [
                                        'number_of_replicas' => 0,
                                        'number_of_shards' => 1,
                                        'refresh_interval' => -1,
                                    ]
                                )
                                ->info('Sets index settings for connection.')
                                ->prototype('variable')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->integerNode('bulk_size')
                        ->min(0)
                        ->defaultValue(100)
                        ->info(
                            'Maximum documents size in the bulk container. ' .
                            'When the limit is reached it will auto-commit.'
                        )
                    ->end()
                    ->enumNode('commit_mode')
                        ->values(['refresh', 'flush', 'none'])
                        ->defaultValue('refresh')
                        ->info(
                            'The default type of commit for bulk queries.'
                        )
                    ->end()
                    ->arrayNode('logger')
                        ->info('Enables elasticsearch queries logging')
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
                                ->info('Sets PSR logging level.')
                                ->defaultValue(LogLevel::WARNING)
                                ->validate()
                                ->ifNotInArray((new \ReflectionClass('Psr\Log\LogLevel'))->getConstants())
                                    ->thenInvalid('Invalid PSR log level.')
                                ->end()
                            ->end()
                            ->scalarNode('log_file_name')
                                ->info('Log filename. By default it is a manager name.')
                                ->defaultValue(null)
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('mappings')
                        ->info('Maps manager to the bundles. f.e. AppBundle')
                        ->prototype('variable')->end()
                    ->end()
                    ->booleanNode('force_commit')
                        ->info('Forces commit to the elasticsearch on kernel terminate event.')
                        ->defaultTrue()
                    ->end()
                ->end()
            ->end();

        return $node;
    }
}
