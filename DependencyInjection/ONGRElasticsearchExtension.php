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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages bundle configuration.
 */
class ONGRElasticsearchExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('es.connections', $config['connections']);
        $container->setParameter('es.managers', $config['managers']);

        $this->addMedadataCollectorDefinition($config, $container);
        $this->addDocumentsResource($config, $container);
        $this->addDataCollectorDefinition($config, $container);
    }

    /**
     * Adds MetadataCollector definition to container.
     *
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function addMedadataCollectorDefinition(array $config, ContainerBuilder $container)
    {
        $metadataCollector = new Definition('ONGR\ElasticsearchBundle\Mapping\MetadataCollector');
        $metadataCollector->setFactoryService('es.metadata_collector_factory');
        $metadataCollector->setFactoryMethod('get');
        $metadataCollector->addMethodCall('setDocumentDir', [$config['document_dir']]);
        $container->setDefinition('es.metadata_collector', $metadataCollector);
    }

    /**
     * Adds document directory file resource.
     *
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function addDocumentsResource(array $config, ContainerBuilder $container)
    {
        $watchedBundles = [];
        foreach ($config['managers'] as $manager) {
            $watchedBundles += $manager['mappings'];
        }

        foreach ($container->getParameter('kernel.bundles') as $name => $class) {
            if (!in_array($name, $watchedBundles)) {
                continue;
            }

            $bundle = new \ReflectionClass($class);
            $dir = dirname($bundle->getFileName()) . DIRECTORY_SEPARATOR . $config['document_dir'];
            $container->addResource(new FileResource($dir));
        }
    }

    /**
     * Adds data collector to container if debug is set to any manager.
     *
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function addDataCollectorDefinition(array $config, ContainerBuilder $container)
    {
        if ($this->isDebugSet($config)) {
            $container->setDefinition('es.logger.trace', $this->getLogTraceDefinition());
            $container->setDefinition('es.collector', $this->getDataCollectorDefinition(['es.logger.trace']));
        }
    }

    /**
     * Finds out if debug is set to any manager.
     *
     * @param array $config
     *
     * @return bool
     */
    private function isDebugSet(array $config)
    {
        foreach ($config['managers'] as $manager) {
            if ($manager['debug']) {
                return true;
            }
        }

        return false;
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

    /**
     * Returns elasticsearch data collector definition.
     *
     * @param array $loggers
     *
     * @return Definition
     */
    private function getDataCollectorDefinition($loggers = [])
    {
        $collector = new Definition('ONGR\ElasticsearchBundle\DataCollector\ElasticsearchDataCollector');
        $collector->addMethodCall('setManagers', [new Parameter('es.managers')]);
        
        
        foreach ($loggers as $logger) {
            $collector->addMethodCall('addLogger', [new Reference($logger)]);
        }

        $collector->addTag(
            'data_collector',
            [
                'template' => 'ONGRElasticsearchBundle:Profiler:profiler.html.twig',
                'id' => 'es',
            ]
        );

        return $collector;
    }
}
