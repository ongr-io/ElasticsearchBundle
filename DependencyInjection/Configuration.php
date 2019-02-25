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

    CONST ONGR_CACHE_CONFIG = 'ongr.esb.cache';
    CONST ONGR_PROFILER_CONFIG = 'ongr.esb.profiler';
    CONST ONGR_LOGGER_CONFIG = 'ongr.esb.logger';
    CONST ONGR_ANALYSIS_CONFIG = 'ongr.esb.analysis';

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
                ->info(
                    'Enables executed queries logging. Log file names are the same as index.'
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
