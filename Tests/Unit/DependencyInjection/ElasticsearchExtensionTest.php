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
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
                'managers' => [
                    'test' => [
                        'index' => [
                            'index_name' => 'test',
                            'hosts' => [
                                '127.0.0.1:9200'
                            ]
                        ],
                        'logger' => [
                            'enabled' => true,
                            'level' => 'warning',
                        ],
                        'mappings' => ['testBundle'],
                    ],
                ],
            ],
        ];

        $expectedManagers = [
            'test' => [
                'index' => [
                    'index_name' => 'test',
                    'hosts' => ['127.0.0.1:9200'],
                    'settings' => [
                        'number_of_replicas' => 0,
                        'number_of_shards' => 1,
                        'refresh_interval' => -1,
                    ],
                ],
                'logger' => [
                    'enabled' => true,
                    'level' => 'warning',
                    'log_file_name' => null,
                ],
                'mappings' => ['testBundle'],
                'bulk_size' => 100,
                'commit_mode' => 'refresh',
                'force_commit' => true,
            ],
        ];

        $out[] = [
            $parameters,
            $expectedManagers,
        ];

        $parameters = [
            'elasticsearch' => [
                'managers' => [
                    'test' => [
                        'index' => ['index_name' => 'test'],
                        'logger' => true,
                        'mappings' => ['testBundle'],
                    ],
                ],
            ],
        ];

        $expectedManagers = [
            'test' => [
                'index' => [
                    'index_name' => 'test',
                    'hosts' => ['127.0.0.1:9200'],
                    'settings' => [
                        'number_of_replicas' => 0,
                        'number_of_shards' => 1,
                        'refresh_interval' => -1,
                    ],
                ],
                'logger' => [
                    'enabled' => true,
                    'level' => 'warning',
                    'log_file_name' => null,
                ],
                'mappings' => ['testBundle'],
                'bulk_size' => 100,
                'commit_mode' => 'refresh',
                'force_commit' => true,
            ],
        ];

        $out[] = [
            $parameters,
            $expectedManagers,
        ];

        return $out;
    }

    /**
     * Check if load adds parameters to container as expected.
     *
     * @param array $parameters
     * @param array $expectedManagers
     *
     * @dataProvider getData
     */
    public function testLoad($parameters, $expectedManagers)
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
