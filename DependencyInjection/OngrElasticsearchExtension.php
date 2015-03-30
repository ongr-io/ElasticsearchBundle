<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages bundle configuration.
 */
class OngrElasticsearchExtension extends Extension
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

        $this->addDocumentFinderDefinition($config, $container);
        $this->addMedadataCollectorDefinition($config, $container);
        $this->addDocumentsResource($config, $container);
        $this->addDataCollectorDefinition($config, $container);

        $this->addClassesToCompile(
            [
                'Ongr\ElasticsearchBundle\Mapping\Proxy\ProxyInterface',
            ]
        );
    }

    /**
     * Adds DocumentFinder definition to container (ID: es.document_finder).
     *
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function addDocumentFinderDefinition(array $config, ContainerBuilder $container)
    {
        $documentFinder = new Definition(
            'Ongr\ElasticsearchBundle\Mapping\DocumentFinder',
            [
                $container->getParameter('kernel.bundles'),
            ]
        );
        $documentFinder->addMethodCall('setDocumentDir', [$config['document_dir']]);
        $container->setDefinition('es.document_finder', $documentFinder);
    }

    /**
     * Adds MetadataCollector definition to container (ID: es.metadata_collector).
     *
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function addMedadataCollectorDefinition(array $config, ContainerBuilder $container)
    {
        $cachedReader = new Definition(
            'Doctrine\Common\Annotations\FileCacheReader',
            [
                new Definition('Doctrine\Common\Annotations\AnnotationReader'),
                $this->getCacheDir($container, 'annotations'),
                $container->getParameter('kernel.debug'),
            ]
        );

        $documentParser = new Definition(
            'Ongr\ElasticsearchBundle\Mapping\DocumentParser',
            [
                $cachedReader,
                new Reference('es.document_finder'),
            ]
        );

        $proxyLoader = new Definition(
            'Ongr\ElasticsearchBundle\Mapping\Proxy\ProxyLoader',
            [
                $this->getCacheDir($container, 'proxies'),
                $container->getParameter('kernel.debug'),
            ]
        );

        $metadataCollector = new Definition(
            'Ongr\ElasticsearchBundle\Mapping\MetadataCollector',
            [
                new Reference('es.document_finder'),
                $documentParser,
                $proxyLoader,
            ]
        );
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
            $watchedBundles = array_merge($watchedBundles, $manager['mappings']);
        }

        $watchedBundles = array_intersect_key(
            $container->getParameter('kernel.bundles'),
            array_flip(array_unique($watchedBundles))
        );

        foreach ($watchedBundles as $name => $class) {
            $bundle = new \ReflectionClass($class);
            $dir = dirname($bundle->getFileName()) . DIRECTORY_SEPARATOR . $config['document_dir'];
            $container->addResource(new DirectoryResource($dir));
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
        $handler = new Definition('Ongr\ElasticsearchBundle\Logger\Handler\CollectionHandler', []);

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
        $collector = new Definition('Ongr\ElasticsearchBundle\DataCollector\ElasticsearchDataCollector');
        $collector->addMethodCall('setManagers', [new Parameter('es.managers')]);

        foreach ($loggers as $logger) {
            $collector->addMethodCall('addLogger', [new Reference($logger)]);
        }

        $collector->addTag(
            'data_collector',
            [
                'template' => 'OngrElasticsearchBundle:Profiler:profiler.html.twig',
                'id' => 'es',
            ]
        );

        return $collector;
    }

    /**
     * Returns cache directory.
     *
     * @param ContainerBuilder $container
     * @param string           $dir
     *
     * @return string
     */
    private function getCacheDir(ContainerBuilder $container, $dir = '')
    {
        return $container->getParameter('kernel.cache_dir')
        . DIRECTORY_SEPARATOR . 'ongr' . DIRECTORY_SEPARATOR . $dir;
    }
}
