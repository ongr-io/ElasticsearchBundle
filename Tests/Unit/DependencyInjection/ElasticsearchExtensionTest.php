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
     * Check if load adds parameters to container as expected.
     */
    public function testLoad()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', []);
        $container->setParameter('kernel.cache_dir', '');
        $container->setParameter('kernel.debug', true);
        $extension = new ONGRElasticsearchExtension();
        $extension->load(
            [
                'elasticsearch' => [
                    'document_dir' => 'customDir',
                    'connections' => [
                        'test' => ['index_name' => 'test'],
                    ],
                    'managers' => [
                        'test' => [
                            'connection' => 'test2',
                            'debug' => false,
                            'mappings' => ['testBundle'],
                        ],
                    ],
                ],
            ],
            $container
        );

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
                'debug' => false,
                'mappings' => ['testBundle'],
            ],
        ];

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
