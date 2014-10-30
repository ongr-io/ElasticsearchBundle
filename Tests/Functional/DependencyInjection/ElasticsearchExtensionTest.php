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
            ['es.manager', 'ONGR\ElasticsearchBundle\ORM\Manager'],
            ['es.manager.default', 'ONGR\ElasticsearchBundle\ORM\Manager'],
            ['es.manager.bar', 'ONGR\ElasticsearchBundle\ORM\Manager'],
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
                'hosts' => [
                    '127.0.0.1:9200',
                ],
                'index_name' => 'ongr-elasticsearch-bundle-test',
                'settings' => [
                    'refresh_interval' => -1,
                    'number_of_replicas' => 0,
                ],
            ],
            'bar' => [
                'hosts' => [
                    '127.0.0.1:9200',
                ],
                'index_name' => 'ongr-elasticsearch-bundle-bar-test',
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
                'mappings' => [
                    'AcmeTestBundle',
                ],
            ],
            'bar' => [
                'connection' => 'bar',
                'mappings' => [
                    'ONGRElasticsearchBundle',
                ],
            ],
        ];
        $actualManagers = $container->getParameter('es.managers');

        $this->assertEquals($expectedConnections, $actualConnections);
        $this->assertEquals($expectedManagers, $actualManagers);
    }
}
