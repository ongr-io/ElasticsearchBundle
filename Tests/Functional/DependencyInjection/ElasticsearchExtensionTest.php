<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DependencyInjection;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ElasticsearchExtensionTest extends WebTestCase
{
    /**
     * @return array
     */
    public function getTestContainerData()
    {
        return [
            [
                'es.manager',
                'ONGR\ElasticsearchBundle\ORM\Manager',
            ],
            [
                'es.manager.default',
                'ONGR\ElasticsearchBundle\ORM\Manager',
            ],
            [
                'es.manager.bar',
                'ONGR\ElasticsearchBundle\ORM\Manager',
            ],
            [
                'es.manager.default.product',
                'ONGR\ElasticsearchBundle\ORM\Repository',
            ],
            [
                'es.manager.default.bar',
                'ONGR\ElasticsearchBundle\ORM\Repository',
            ],
            [
                'es.manager.default.color',
                'ONGR\ElasticsearchBundle\ORM\Repository',
            ],
            [
                'es.manager.default.colordocument',
                'ONGR\ElasticsearchBundle\ORM\Repository',
            ],
            [
                'es.manager.default.media',
                'ONGR\ElasticsearchBundle\ORM\Repository',
            ],
            [
                'es.metadata_collector',
                'ONGR\ElasticsearchBundle\Mapping\MetadataCollector',
            ],
        ];
    }

    /**
     * Tests if container has all services.
     *
     * @param string $id
     * @param string $instance
     *
     * @dataProvider getTestContainerData
     */
    public function testContainer($id, $instance)
    {
        $container = static::createClient()->getContainer();

        $this->assertTrue($container->has($id), 'Container should have setted id.');
        $this->assertInstanceOf($instance, $container->get($id), 'Container has wrong instance set to id.');
    }

    /**
     * Test if container sets the default values as expected.
     */
    public function testContainerDefaultParams()
    {
        $container = $this->createClient()->getContainer();

        $expectedConnections = [
            'default' => [
                'settings' => [
                    'refresh_interval' => -1,
                    'number_of_replicas' => 0,
                ],
            ],
            'bar' => [
                'settings' => [
                    'refresh_interval' => -1,
                    'number_of_replicas' => 1,
                ],
            ],
        ];
        $actualConnections = $container->getParameter('es.connections');

        $expectedManagers = [
            'default' => [
                'connection' => 'default',
                'debug' => true,
                'readonly' => false,
                'mappings' => [
                    'AcmeTestBundle',
                    'AcmeFooBundle:Media',
                ],
            ],
            'bar' => [
                'connection' => 'bar',
                'debug' => false,
                'readonly' => false,
                'mappings' => ['ONGRElasticsearchBundle'],
            ],
            'readonly' => [
                'connection' => 'default',
                'debug' => true,
                'readonly' => true,
                'mappings' => ['AcmeTestBundle'],
            ],
        ];
        $actualManagers = $container->getParameter('es.managers');

        $this->assertArraySubset($expectedConnections, $actualConnections);
        $this->assertEquals($expectedManagers, $actualManagers);
    }
}
