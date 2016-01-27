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
use ONGR\ElasticsearchBundle\Mapping\DocumentFinder;
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
                'connections' => [
                    'test' => ['index_name' => 'test'],
                ],
                'managers' => [
                    'test' => [
                        'connection' => 'test2',
                        'logger' => [
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
                'analysis' => [
                    'tokenizer' => [],
                    'filter' => [],
                    'analyzer' => [],
                ],
            ],
        ];

        $expectedManagers = [
            'test' => [
                'connection' => 'test2',
                'logger' => [
                    'enabled' => true,
                    'level' => 'warning',
                    'log_file_name' => null,
                ],
                'readonly' => false,
                'mappings' => ['testBundle'],
                'bulk_size' => 100,
                'commit_mode' => 'refresh',
            ],
        ];

        $out[] = [
            $parameters,
            $expectedConnections,
            $expectedManagers,
        ];

        $parameters = [
            'elasticsearch' => [
                'connections' => [
                    'test' => ['index_name' => 'test'],
                ],
                'managers' => [
                    'test' => [
                        'connection' => 'test2',
                        'logger' => true,
                        'readonly' => false,
                        'mappings' => ['testBundle'],
                    ],
                ],
            ],
        ];

        $expectedManagers = [
            'test' => [
                'connection' => 'test2',
                'logger' => [
                    'enabled' => true,
                    'level' => 'warning',
                    'log_file_name' => null,
                ],
                'readonly' => false,
                'mappings' => ['testBundle'],
                'bulk_size' => 100,
                'commit_mode' => 'refresh',
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
