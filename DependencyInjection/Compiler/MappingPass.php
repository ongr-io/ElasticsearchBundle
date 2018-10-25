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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;

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
        $managers = $container->getParameter('es.managers');

        $collector = $container->get('es.metadata_collector');

        foreach ($managers as $managerName => $manager) {
            $connection = $manager['index'];
            $managerName = strtolower($managerName);

            $managerDefinition = new Definition(
                'ONGR\ElasticsearchBundle\Service\Manager',
                [
                    $managerName,
                    $connection,
                    $analysis,
                    $manager,
                ]
            );
            $managerDefinition->setPublic(true);
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

                if (Kernel::MAJOR_VERSION >= 4) {
                    $container->getAlias('es.manager')
                        ->setPublic(true)
                    ;
                }
            }

            $mappings = $collector->getMappings($manager['mappings']);

            // Building repository services.
            foreach ($mappings as $repositoryType => $repositoryDetails) {
                $repositoryDefinition = new Definition(
                    'ONGR\ElasticsearchBundle\Service\Repository',
                    [$repositoryDetails['namespace']]
                );
                $repositoryDefinition->setPublic(true);

                if (isset($repositoryDetails['directory_name']) && $managerName == 'default') {
                    $container->get('es.document_finder')->setDocumentDir($repositoryDetails['directory_name']);
                }

                $repositoryDefinition->setFactory(
                    [
                        new Reference(sprintf('es.manager.%s', $managerName)),
                        'getRepository',
                    ]
                );

                $repositoryId = sprintf('es.manager.%s.%s', $managerName, $repositoryType);
                $container->setDefinition($repositoryId, $repositoryDefinition);
            }
        }
    }
}
