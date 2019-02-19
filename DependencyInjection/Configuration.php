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
    CONST ONGR_CACHE_CONFIG = 'ongr.esb.cache';
    CONST ONGR_PROFILER_CONFIG = 'ongr.esb.profiler';
    CONST ONGR_ANALYSIS_CONFIG = 'ongr.esb.analysis';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ongr_elasticsearch');

        $rootNode
            ->children()

            ->booleanNode('cache')
                ->info(
                    'Enables the cache handler to store important data to the cache. '.
                    'Default value is kernel.debug parameter.'
                )
            ->end()

            ->booleanNode('profiler')
                ->info(
                    'Enables Symfony profiler for the elasticsearch queries debug.'.
                    'Default value is kernel.debug parameter. '
                )
            ->end()

            ->arrayNode('include_dir')
                ->info('Here you can include additional directories if you have index documents somewhere out of your project `src/` directory.')
                ->defaultValue([])
                ->prototype('scalar')->end()
            ->end()

            ->append($this->getAnalysisNode())

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
}
