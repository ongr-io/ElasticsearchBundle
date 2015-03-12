<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DependencyInjection\Compiler;

use ONGR\ElasticsearchBundle\DependencyInjection\Compiler\MappingPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Unit tests for MappingPass.
 */
class MappingPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * Before a test method is run, a template method called setUp() is invoked.
     */
    public function setUp()
    {
        $bundleMappingData = ['class' => 'Comment', 'type' => 'comment'];
        $metadataCollectorMock = $this->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\MetadataCollector')
            ->disableOriginalConstructor()
            ->getMock();

        $metadataCollectorMock->expects($this->any())->method('getClientMapping')->willReturn(
            [
                'AcmeTestBundle:Bar' => [
                    'properties' => [],
                ],
                'AcmeTestBundle:Product' => [
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ]
        );
        $metadataCollectorMock->expects($this->any())->method('getMapping')->willReturn([$bundleMappingData]);
        $metadataCollectorMock->expects($this->any())->method('getProxyPaths')->willReturn([]);

        $this->container = $this->getMockBuilder('\Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->expects($this->any())->method('findTaggedServiceIds')->willReturn([]);
        $this->container->expects($this->any())->method('get')->with($this->anything())
            ->will(
                $this->returnCallback(
                    function ($parameter) use ($metadataCollectorMock) {
                        switch ($parameter) {
                            case 'es.metadata_collector':
                                return $metadataCollectorMock;
                            default:
                                return null;
                        }
                    }
                )
            );
    }

    /**
     * Test process with several managers.
     */
    public function testProcessWithSeveralManagers()
    {
        $expectedConnections = [
            'default' => [
                'hosts' => ['127.0.0.1:9200'],
                'index_name' => 'ongr-elasticsearch-bundle-test',
                'settings' => [
                    'refresh_interval' => -1,
                    'number_of_replicas' => 0,
                ],
                'auth' => [
                    'username' => 'user',
                    'password' => 'pass',
                ],
            ],
            'bar' => [
                'hosts' => ['127.0.0.1:9200'],
                'index_name' => 'ongr-elasticsearch-bundle-bar-test',
                'settings' => [
                    'refresh_interval' => -1,
                    'number_of_replicas' => 1,
                ],
            ],
        ];

        $expectedManagers = [
            'default' => [
                'connection' => 'default',
                'debug' => true,
                'readonly' => false,
                'mappings' => ['AcmeTestBundle'],
            ],
            'bar' => [
                'connection' => 'bar',
                'debug' => false,
                'readonly' => false,
                'mappings' => ['ONGRElasticsearchBundle'],
            ],
        ];

        $this->container->expects($this->any())
            ->method('getParameter')
            ->with($this->anything())
            ->will(
                $this->returnCallback(
                    function ($parameters) use ($expectedConnections, $expectedManagers) {
                        switch ($parameters) {
                            case 'es.connections':
                                return $expectedConnections;
                            case 'es.managers':
                                return $expectedManagers;
                            default:
                                return null;
                        }
                    }
                )
            );
        $compilerPass = new MappingPass();
        $compilerPass->process($this->container);
    }

    /**
     * Check if exception is thrown when there is no connection.
     *
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage There is no ES connection with name foo
     */
    public function testProcessWithNotExistingConnection()
    {
        $expectedManagers = [
            'default' => [
                'connection' => 'foo',
                'debug' => true,
                'readonly' => false,
                'mappings' => ['AcmeTestBundle'],
            ],
        ];
        $this->container->expects($this->any())
            ->method('getParameter')
            ->with($this->anything())
            ->will(
                $this->returnCallback(
                    function ($parameters) use ($expectedManagers) {
                        switch ($parameters) {
                            case 'es.managers':
                                return $expectedManagers;
                            default:
                                return null;
                        }
                    }
                )
            );
        $compilerPass = new MappingPass();
        $compilerPass->process($this->container);
    }

    /**
     * Check when Manager Mapping is empty.
     */
    public function testWhenManagerMappingIsEmpty()
    {
        $expectedConnections = [
            'default' => [
                'hosts' => ['127.0.0.1:9200'],
                'index_name' => 'ongr-elasticsearch-bundle-test',
                'settings' => [
                    'refresh_interval' => -1,
                    'number_of_replicas' => 0,
                ],
            ],
        ];
        $expectedManagers = [
            'default' => [
                'connection' => 'default',
                'debug' => true,
                'readonly' => false,
                'mappings' => [],
            ],
        ];

        $kernelBundles = [];

        $this->container->expects($this->any())
            ->method('getParameter')
            ->with($this->anything())
            ->will(
                $this->returnCallback(
                    function ($parameters) use ($expectedConnections, $expectedManagers, $kernelBundles) {
                        switch ($parameters) {
                            case 'es.connections':
                                return $expectedConnections;
                            case 'es.managers':
                                return $expectedManagers;
                            case 'kernel.bundles':
                                return $expectedManagers;
                            default:
                                return null;
                        }
                    }
                )
            );

        $compilerPass = new MappingPass();
        $compilerPass->process($this->container);
    }
}
