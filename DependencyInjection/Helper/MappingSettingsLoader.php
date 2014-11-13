<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\DependencyInjection\Helper;

use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * MappingSettingsLoader class.
 */
class MappingSettingsLoader
{
    /**
     * @param array             $connection
     * @param array             $settings
     * @param ContainerBuilder  $container
     * @param MetadataCollector $metadataCollector
     *
     * @return array
     */
    public function getSettings($connection, $settings, $container, $metadataCollector)
    {
        $params = ['hosts' => $connection['hosts']];

        if (!empty($connection['auth'])) {
            $params['connectionParams']['auth'] = array_values($connection['auth']);
        }

        if ($settings['debug']) {
            $params['logging'] = true;
            $params['logPath'] = $container->getParameter('es.connection.logging')['logPath'];
            $params['logLevel'] = LogLevel::WARNING;
            $params['tracePath'] = $container->getParameter('es.connection.logging')['tracePath'];
            $params['traceLevel'] = LogLevel::DEBUG;
        }

        $index = [
            'index' => $connection['index_name'],
        ];

        if (!empty($connection['settings'])) {
            $index['body']['settings'] = $connection['settings'];
        }

        $mappings = [];

        if (!empty($settings['mappings'])) {
            foreach ($settings['mappings'] as $bundle) {
                $mappings = array_replace_recursive(
                    $mappings,
                    $metadataCollector->getMapping($bundle)
                );
            }
        } else {
            foreach ($container->getParameter('kernel.bundles') as $bundle => $path) {
                $mappings = array_replace_recursive(
                    $mappings,
                    $metadataCollector->getMapping($bundle)
                );
            }
        }

        if (!empty($mappings)) {
            $index['body']['mappings'] = $mappings;
        }

        return [$params, $index];
    }
}
