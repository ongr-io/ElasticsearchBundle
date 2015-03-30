<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Functional\DependencyInjection;

use Ongr\ElasticsearchBundle\Test\TestHelperTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ElasticsearchExtensionTest extends WebTestCase
{
    use TestHelperTrait;

    /**
     * @return array
     */
    public function getTestContainerData()
    {
        return [
            [
                'es.manager',
                'Ongr\ElasticsearchBundle\ORM\Manager',
            ],
            [
                'es.manager.default',
                'Ongr\ElasticsearchBundle\ORM\Manager',
            ],
            [
                'es.manager.bar',
                'Ongr\ElasticsearchBundle\ORM\Manager',
            ],
            [
                'es.manager.default.product',
                'Ongr\ElasticsearchBundle\ORM\Repository',
            ],
            [
                'es.manager.default.bar',
                'Ongr\ElasticsearchBundle\ORM\Repository',
            ],
            [
                'es.manager.default.color',
                'Ongr\ElasticsearchBundle\ORM\Repository',
            ],
            [
                'es.manager.default.colordocument',
                'Ongr\ElasticsearchBundle\ORM\Repository',
            ],
            [
                'es.manager.default.media',
                'Ongr\ElasticsearchBundle\ORM\Repository',
            ],
            [
                'es.metadata_collector',
                'Ongr\ElasticsearchBundle\Mapping\MetadataCollector',
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
                'mappings' => ['OngrElasticsearchBundle'],
            ],
            'readonly' => [
                'connection' => 'default',
                'debug' => true,
                'readonly' => true,
                'mappings' => ['AcmeTestBundle'],
            ],
        ];
        $actualManagers = $container->getParameter('es.managers');

        $this->assertArrayContainsArray($expectedConnections, $actualConnections);
        $this->assertEquals($expectedManagers, $actualManagers);
    }
}
