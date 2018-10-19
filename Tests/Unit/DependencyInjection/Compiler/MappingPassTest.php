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
use Symfony\Component\DependencyInjection\Alias;

/**
 * Unit tests for MappingPass.
 */
class MappingPassTest extends \PHPUnit_Framework_TestCase
{

    public function getConfigurationData()
    {
        return [
            [
                'managers' => [
                    'default' => [
                        'index' => [
                            'hosts' => ['127.0.0.1:9200'],
                            'index_name' => 'acme',
                            'settings' => [
                                'refresh_interval' => -1,
                                'number_of_replicas' => 1,
                            ],
                        ],
                        'debug' => true,
                        'mappings' => ['TestBundle'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $managers
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function getContainerMock(array $managers)
    {
        $metadataCollectorMock = $this->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\MetadataCollector')
            ->disableOriginalConstructor()
            ->getMock();

        $metadataCollectorMock->expects($this->once())->method('getMappings')->willReturn(
            [
                'product' => [
                    'properties' => [],
                    'bundle' => 'TestBundle',
                    'class' => 'Foo',
                    'namespace' => 'TestBundle\Document\Foo',
                ],
            ]
        );

        $containerMock = $this->getMockBuilder('\Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $containerMock->expects($this->exactly(2))->method('getParameter')->with($this->anything())
            ->will(
                $this->returnCallback(
                    function ($parameter) use ($managers) {
                        switch ($parameter) {
                            case 'es.managers':
                                return $managers;
                            default:
                                return null;
                        }
                    }
                )
            );

        $containerMock->expects($this->once())->method('get')->with($this->anything())
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
        $containerMock
            ->expects($this->exactly(2))
            ->method('setDefinition')
            ->withConsecutive(
                [$this->equalTo('es.manager.default')],
                [$this->equalTo('es.manager.default.product')]
            )
            ->willReturn(null);

        $containerMock
            ->expects($this->exactly(1))
            ->method('setAlias')
            ->withConsecutive(
                [$this->equalTo('es.manager')],
                [$this->equalTo('es.manager.default')]
            )
            ->willReturn(new Alias('es.manager', 'es.manager.default'));

        return $containerMock;
    }

    /**
     * Before a test method is run, a template method called setUp() is invoked.
     *
     * @param array $managers
     *
     * @dataProvider getConfigurationData()
     */
    public function testProcessWithSeveralManagers(array $managers)
    {
        $compilerPass = new MappingPass();
        $compilerPass->process($this->getContainerMock($managers));
    }
}
