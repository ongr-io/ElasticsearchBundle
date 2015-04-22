<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DependencyInjection;

use ONGR\ElasticsearchBundle\DependencyInjection\ONGRElasticsearchExtension;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Unit tests for ElasticsearchExtension.
 */
class ElasticsearchExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function getData()
    {
        $parameters = [
            'elasticsearch' => [
                'document_dir' => 'customDir',
                'connections' => [
                    'test' => ['index_name' => 'test'],
                ],
                'managers' => [
                    'test' => [
                        'connection' => 'test2',
                        'debug' => [
                            'enabled' => true,
                            'level' => 'warning',
                        ],
                        'readonly' => false,
                        'mappings' => ['testBundle'],
                    ],
                ],
            ],
        ];

        $expectedConnections = [
            'test' => [
                'index_name' => 'test',
                'hosts' => ['127.0.0.1:9200'],
                'settings' => [],
            ],
        ];

        $expectedManagers = [
            'test' => [
                'connection' => 'test2',
                'debug' => [
                    'enabled' => true,
                    'level' => 'warning',
                ],
                'readonly' => false,
                'mappings' => ['testBundle'],
            ],
        ];

        $out[] = [
            $parameters,
            $expectedConnections,
            $expectedManagers,
        ];

        $parameters = [
            'elasticsearch' => [
                'document_dir' => 'customDir',
                'connections' => [
                    'test' => ['index_name' => 'test'],
                ],
                'managers' => [
                    'test' => [
                        'connection' => 'test2',
                        'debug' => true,
                        'readonly' => false,
                        'mappings' => ['testBundle'],
                    ],
                ],
            ],
        ];

        $expectedManagers = [
            'test' => [
                'connection' => 'test2',
                'debug' => [
                    'enabled' => true,
                    'level' => 'warning',
                ],
                'readonly' => false,
                'mappings' => ['testBundle'],
            ],
        ];

        $out[] = [
            $parameters,
            $expectedConnections,
            $expectedManagers,
        ];

        return $out;
    }

    /**
     * Check if load adds parameters to container as expected.
     *
     * @param array $parameters
     * @param array $expectedConnections
     * @param array $expectedManagers
     *
     * @dataProvider getData
     */
    public function testLoad($parameters, $expectedConnections, $expectedManagers)
    {
        $container = new ContainerBuilder();
        class_exists('testClass') ? : eval('class testClass {}');
        $container->setParameter('kernel.bundles', ['testBundle' => 'testClass']);
        $container->setParameter('kernel.cache_dir', '');
        $container->setParameter('kernel.debug', true);
        $extension = new ONGRElasticsearchExtension();
        $extension->load(
            $parameters,
            $container
        );

        if ($parameters['elasticsearch']['managers']['test']['debug']) {
            $reflection = new \ReflectionClass($this);
            $dir = dirname($reflection->getFileName()) . DIRECTORY_SEPARATOR . 'customDir';

            $handler = new Definition('ONGR\ElasticsearchBundle\Logger\Handler\CollectionHandler', []);
            $logger = new Definition(
                'Monolog\Logger',
                [
                    'tracer',
                    [$handler],
                ]
            );

            $collector = new Definition('ONGR\ElasticsearchBundle\DataCollector\ElasticsearchDataCollector');
            $collector->addMethodCall('setManagers', [new Parameter('es.managers')]);
            $collector->addMethodCall('addLogger', [new Reference('es.logger.trace')]);
            $collector->addTag(
                'data_collector',
                [
                    'template' => 'ONGRElasticsearchBundle:Profiler:profiler.html.twig',
                    'id' => 'es',
                ]
            );

            $this->assertEquals(
                $collector,
                $container->getDefinition('es.collector')
            );
            $this->assertEquals(
                $logger,
                $container->getDefinition('es.logger.trace')
            );
            $this->assertEquals(
                new DirectoryResource($dir),
                $container->getResources()[1]
            );
        }

        $this->assertEquals(
            $expectedConnections,
            $container->getParameter('es.connections'),
            'Incorrect connections parameter.'
        );
        $this->assertEquals(
            $expectedManagers,
            $container->getParameter('es.managers'),
            'Incorrect managers parameter'
        );
        $this->assertTrue(
            $container->hasDefinition('es.metadata_collector'),
            'Container should have MetadataCollector definition set.'
        );
    }
}
