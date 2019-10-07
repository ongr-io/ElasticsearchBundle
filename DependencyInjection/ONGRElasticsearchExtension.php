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

use ONGR\ElasticsearchBundle\Mapping\Converter;
use ONGR\ElasticsearchBundle\Mapping\DocumentParser;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class ONGRElasticsearchExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter(
            Configuration::ONGR_CACHE_CONFIG,
            $config['cache'] ?? $container->getParameter('kernel.debug')
        );

        $container->setParameter(
            Configuration::ONGR_PROFILER_CONFIG,
            $config['profiler'] ?? $container->getParameter('kernel.debug')
        );

        $container->setParameter(
            Configuration::ONGR_LOGGER_CONFIG,
            $config['logger'] ?? $container->getParameter('kernel.debug')
        );

        $container->setParameter(Configuration::ONGR_INDEXES_OVERRIDE, $config['indexes']);
        $container->setParameter(Configuration::ONGR_ANALYSIS_CONFIG, $config['analysis']);
        $container->setParameter(Configuration::ONGR_SOURCE_DIR, $config['source_directories']);
    }
}
