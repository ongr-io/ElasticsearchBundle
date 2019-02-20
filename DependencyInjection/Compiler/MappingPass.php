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

use ONGR\ElasticsearchBundle\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;

/**
 * Compiles elastic search data.
 */
class MappingPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        $analysis = $container->getParameter(Configuration::ONGR_ANALYSIS_CONFIG);
        $additionalDirs = $container->getParameter(Configuration::ONGR_INCLUDE_DIR_CONFIG);

        $collector = $container->get('es.metadata_collector');

        $kernelProjectDir = $container->getParameter('kernel.project_dir');

        $namespaces = array();

        $finder = new Finder();
        $projectFiles = $finder->files()->in(
            array_merge([
                $kernelProjectDir . '/src'
            ], $additionalDirs)
        )->name('*.php');

        foreach ($projectFiles as $file) {
            $namespaces[] = $this->getFullNamespace($file) . '\\' . $this->getClassname($file);
        }

        $indexDefinition = new Definition(
            'ONGR\ElasticsearchBundle\Service\IndexService',
            []
        );

        $container->autowire()

    }


        private function getFullNamespace($filename) {
            $lines = file($filename);
            $namespaceLine = array_shift(preg_grep('/^namespace /', $lines));
            $match = array();
            preg_match('/^namespace (.*);$/', $namespaceLine, $match);
            $fullNamespace = array_pop($match);

            return $fullNamespace;
        }

       private function getClassname($filename) {
            $directoriesAndFilename = explode('/', $filename);
            $filename = array_pop($directoriesAndFilename);
            $nameAndExtension = explode('.', $filename);
            $className = array_shift($nameAndExtension);

            return $className;
        }






//
//
//        foreach ($managers as $managerName => $manager) {
//            $connection = $manager['index'];
//            $managerName = strtolower($managerName);
//
//            $managerDefinition = new Definition(
//                'ONGR\ElasticsearchBundle\Service\Manager',
//                [
//                    $managerName,
//                    $connection,
//                    $analysis,
//                    $manager,
//                ]
//            );
//            $managerDefinition->setFactory(
//                [
//                    new Reference('es.manager_factory'),
//                    'createManager',
//                ]
//            );
//
//            $container->setDefinition(sprintf('es.manager.%s', $managerName), $managerDefinition);
//
//            // Make es.manager.default as es.manager service.
//            if ($managerName === 'default') {
//                $container->setAlias('es.manager', 'es.manager.default');
//            }
//
//            $mappings = $collector->getMappings($manager['mappings']);
//
//            // Building repository services.
//            foreach ($mappings as $repositoryType => $repositoryDetails) {
//                $repositoryDefinition = new Definition(
//                    'ONGR\ElasticsearchBundle\Service\Repository',
//                    [$repositoryDetails['namespace']]
//                );
//
//                $repositoryDefinition->setFactory(
//                    [
//                        new Reference(sprintf('es.manager.%s', $managerName)),
//                        'getRepository',
//                    ]
//                );
//
//                $repositoryId = sprintf('es.manager.%s.%s', $managerName, $repositoryType);
//                $container->setDefinition($repositoryId, $repositoryDefinition);
//            }
//        }
//    }
}
