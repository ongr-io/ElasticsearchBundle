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
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    const ONGR_CACHE_CONFIG = 'ongr.esb.cache';
    const ONGR_SOURCE_DIR = 'ongr.esb.source_dir';
    const ONGR_PROFILER_CONFIG = 'ongr.esb.profiler';
    const ONGR_LOGGER_CONFIG = 'ongr.esb.logger';
    const ONGR_ANALYSIS_CONFIG = 'ongr.esb.analysis';
    const ONGR_INDEXES = 'ongr.esb.indexes';
    const ONGR_DEFAULT_INDEX = 'ongr.esb.default_index';

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

            ->booleanNode('logger')
                ->defaultTrue()
                ->info(
                    'Enables executed queries logging. Log file names are the same as index.'
                )
            ->end()

            ->scalarNode('source_directory')
                ->defaultValue('/src')
                ->info(
                    'If your project has different than `/src` source directory, you can specify it here '.
                    'to look automatically for ES documents.'
                )
            ->end()

            ->append($this->getAnalysisNode())

            ->end();

        return $treeBuilder;
    }

    private function getAnalysisNode(): NodeDefinition
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
