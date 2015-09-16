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

use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
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
        $analysis = $container->getParameter('es.analysis');
        $connections = $container->getParameter('es.connections');
        $managers = $container->getParameter('es.managers');

        $collector = $container->get('es.metadata_collector');

        foreach ($managers as $managerName => $manager) {
            if (!isset($connections[$manager['connection']])) {
                throw new InvalidConfigurationException(
                    'There is no ES connection with the name: ' . $manager['connection']
                );
            }

            $managerName = strtolower($managerName);
            $connection = $connections[$manager['connection']];

            $managerDefinition = new Definition(
                $container->getParameter('es.manager.class'),
                [
                    $managerName,
                    $connection,
                    $analysis,
                    $manager,
                ]
            );
            $managerDefinition->setFactory(
                [
                    new Reference('es.manager_factory'),
                    'createManager',
                ]
            );

            $container->setDefinition(sprintf('es.manager.%s', $managerName), $managerDefinition);

            // Make es.manager.default as es.manager service.
            if ($managerName === 'default') {
                $container->setAlias('es.manager', 'es.manager.default');
            }

            $mappings = $collector->getMappings($manager['mappings']);

            // Building repository services.
            /** @var Definition $data */
            foreach ($mappings as $repositoryType => $repositoryDetails) {
                $repositoryDefinition = new Definition(
                    $container->getParameter('es.repository.class'),
                    [$repositoryDetails['bundle'].':'.$repositoryDetails['class']]
                );
                $repositoryDefinition->setFactory(
                    [
                        new Reference(sprintf('es.manager.%s', $managerName)),
                        'getRepository',
                    ]
                );

                $repositoryId = sprintf('es.manager.%s.%s', $managerName, $repositoryType);

                if (strtolower(substr($repositoryType, -8)) === 'document') {
                    $container->setAlias(
                        sprintf('es.manager.%s.%s', $managerName, substr($repositoryType, 0, strlen($repositoryType) - 8)),
                        $repositoryId
                    );
                }

                $container->setDefinition($repositoryId, $repositoryDefinition);
            }
        }
    }
}
