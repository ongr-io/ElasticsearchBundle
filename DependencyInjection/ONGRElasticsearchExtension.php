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

        $metadataCollector = new Definition('ONGR\ElasticsearchBundle\Mapping\MetadataCollector');
        $metadataCollector->setFactoryService('es.metadata_collector_factory');
        $metadataCollector->setFactoryMethod('get');
        $metadataCollector->addMethodCall('setDocumentDir', [$config['document_dir']]);
        $container->setDefinition('es.metadata_collector', $metadataCollector);

        // Watch documents.

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
}
