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
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

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
            $container->setDefinition('es.logger.trace', $this->getLogTraceDefinition());
            $params['logging'] = true;
            $params['logPath'] = $container->getParameter('es.logging.path');
            $params['logLevel'] = LogLevel::WARNING;
            $params['traceObject'] = new Reference('es.logger.trace');
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

        return [
            $params,
            $index,
        ];
    }

    /**
     * Returns logger used for collecting data.
     *
     * @return Definition
     */
    private function getLogTraceDefinition()
    {
        $handler = new Definition('ONGR\ElasticsearchBundle\Logger\Handler\CollectionHandler', []);

        $logger = new Definition(
            'Monolog\Logger',
            [
                'tracer',
                [$handler],
            ]
        );

        return $logger;
    }
}
