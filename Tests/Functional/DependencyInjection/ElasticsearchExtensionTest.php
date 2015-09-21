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
                'ONGR\ElasticsearchBundle\Service\Manager',
            ],
            [
                'es.manager.default',
                'ONGR\ElasticsearchBundle\Service\Manager',
            ],
            [
                'es.manager.foo',
                'ONGR\ElasticsearchBundle\Service\Manager',
            ],
            [
                'es.manager.default.product',
                'ONGR\ElasticsearchBundle\Service\Repository',
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

        $this->assertTrue($container->has($id), 'Container should have set id.');
        $this->assertInstanceOf($instance, $container->get($id), 'Container has wrong instance set to id.');
    }

    /**
     * Test if container sets the default values as expected.
     */
    public function testContainerDefaultParams()
    {
        $container = $this->createClient()->getContainer();

        $expectedManagers = ['default', 'foo', 'readonly'];
        $actualManagers = $container->getParameter('es.managers');

        $this->assertEquals($expectedManagers, array_keys($actualManagers));
    }
}
