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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;

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

        if (Kernel::MAJOR_VERSION >= 4) {
            $loader->load('services4.yml');
        }

        $config['cache'] = isset($config['cache']) ?
            $config['cache'] : !$container->getParameter('kernel.debug');
        $config['profiler'] = isset($config['profiler']) ?
            $config['profiler'] : $container->getParameter('kernel.debug');

        $container->setParameter('es.cache', $config['cache']);
        $container->setParameter('es.analysis', $config['analysis']);
        $container->setParameter('es.managers', $config['managers']);
        $definition = new Definition(
            'ONGR\ElasticsearchBundle\Service\ManagerFactory',
            [
                new Reference('es.metadata_collector'),
                new Reference('es.result_converter'),
                $config['profiler'] ? new Reference('es.tracer') : null,
            ]
        );
        $definition->addMethodCall('setEventDispatcher', [new Reference('event_dispatcher')]);
        $definition->addMethodCall(
            'setStopwatch',
            [
                new Reference('debug.stopwatch', ContainerInterface::NULL_ON_INVALID_REFERENCE)
            ]
        );
        $definition->setPublic(true);
        $container->setDefinition('es.manager_factory', $definition);
    }
}
