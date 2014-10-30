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

use ONGR\ElasticsearchBundle\DependencyInjection\Helper\MappingSettingsLoader;
use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

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
        /** @var MetadataCollector $metadataCollector */
        $metadataCollector = $container->get('es.metadata_collector');
        $connections = $container->getParameter('es.connections');
        $managers = $container->getParameter('es.managers');
        $settingsHelper = new MappingSettingsLoader();

        foreach ($managers as $managerName => $settings) {
            if (isset($connections[$settings['connection']])) {
                list($parameters, $index) = $settingsHelper->getSettings(
                    $connections[$settings['connection']],
                    $settings,
                    $container,
                    $metadataCollector
                );
            } else {
                throw new InvalidConfigurationException(
                    'There is no ES connection with name ' . $settings['connection']
                );
            }

            $client = new Definition(
                'Elasticsearch\Client',
                [ 'hosts' => $parameters ]
            );

            $clientConnection = new Definition(
                'ONGR\ElasticsearchBundle\Client\Connection',
                [$client, $index]
            );

            $bundlesMetadata = [];
            $typeMapping = [];

            foreach ($settings['mappings'] as $bundle) {
                $data = $metadataCollector->getBundleMapping($bundle);
                foreach ($data as $typeName => $typeParams) {
                    $typeParams['type'] = $typeName;
                    $repositoryName = $bundle . ':' . $typeParams['class'];
                    $bundlesMetadata[$repositoryName] = $typeParams;
                    $typeMapping[$typeName] = $repositoryName;
                }
            }

            $manager = new Definition(
                'ONGR\ElasticsearchBundle\ORM\Manager',
                [$clientConnection, $container->getDefinition('es.metadata_collector'), $typeMapping, $bundlesMetadata]
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
}
