<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\DependencyInjection\Compiler;

use Psr\Log\LogLevel;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiles elastic search data.
 */
class MappingPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $connections = $container->getParameter('es.connections');
        $managers = $container->getParameter('es.managers');

        foreach ($managers as $managerName => $settings) {
            if (isset($connections[$settings['connection']])) {
                $parameters = $this->getClientParams($connections[$settings['connection']], $settings, $container);
                $index = $this->getIndexParams($connections[$settings['connection']], $settings, $container);
            } else {
                throw new InvalidConfigurationException(
                    'There is no ES connection with name ' . $settings['connection']
                );
            }

            $client = new Definition('Elasticsearch\Client', [$parameters]);
            $clientConnection = new Definition(
                'ONGR\ElasticsearchBundle\Client\Connection',
                [
                    $client,
                    $index,
                ]
            );

            $bundlesMetadata = [];
            $typeMapping = [];

            foreach ($settings['mappings'] as $bundle) {
                $data = $container->get('es.metadata_collector')->getBundleMapping($bundle);
                foreach ($data as $typeName => $typeParams) {
                    $typeParams['type'] = $typeName;
                    $repositoryName = $bundle . ':' . $typeParams['class'];
                    $bundlesMetadata[$repositoryName] = $typeParams;
                    $typeMapping[$typeName] = $repositoryName;
                }
            }

            $manager = new Definition(
                'ONGR\ElasticsearchBundle\ORM\Manager',
                [
                    $clientConnection,
                    $container->getDefinition('es.metadata_collector'),
                    $typeMapping,
                    $bundlesMetadata,
                ]
            );

            $managerName = strtolower($managerName);

            $container->setDefinition(
                sprintf('es.manager.%s', $managerName),
                $manager
            );

            if ($managerName === 'default') {
                $container->setAlias('es.manager', 'es.manager.default');
            }

            foreach ($bundlesMetadata as $repo => $data) {
                $repository = new Definition('ONGR\ElasticsearchBundle\ORM\Repository', [$manager, [$repo]]);
                $container->setDefinition(
                    sprintf('es.manager.%s.%s', $managerName, strtolower($data['class'])),
                    $repository
                );
            }
        }
    }

    /**
     * Returns params for client.
     *
     * @param array            $connection
     * @param array            $manager
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function getClientParams(array $connection, array $manager, ContainerBuilder $container)
    {
        $params = ['hosts' => $connection['hosts']];

        if (!empty($connection['auth'])) {
            $params['connectionParams']['auth'] = array_values($connection['auth']);
        }

        if ($manager['debug']) {
            $params['logging'] = true;
            $params['logPath'] = $container->getParameter('es.logging.path');
            $params['logLevel'] = LogLevel::WARNING;
            $params['traceObject'] = new Reference('es.logger.trace');
        }

        return $params;
    }

    /**
     * Returns params for index.
     *
     * @param array            $connection
     * @param array            $manager
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function getIndexParams(array $connection, array $manager, ContainerBuilder $container)
    {
        $index = ['index' => $connection['index_name']];

        if (!empty($connection['settings'])) {
            $index['body']['settings'] = $connection['settings'];
        }

        $mappings = [];
        $metadataCollector = $container->get('es.metadata_collector');

        if (!empty($manager['mappings'])) {
            foreach ($manager['mappings'] as $bundle) {
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

        return $index;
    }
}
