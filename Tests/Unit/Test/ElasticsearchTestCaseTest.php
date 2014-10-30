<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Test;

use ONGR\ElasticsearchBundle\Client\Connection;
use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use ONGR\ElasticsearchBundle\ORM\Manager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests ElasticsearchTestCase.
 */
class ElasticsearchTestCaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Connection
     */
    private $connectionMock;

    /**
     * @var MetadataCollector
     */
    private $collectorMock;

    /**
     * @var Manager
     */
    private $managerMock;

    /**
     * @var ContainerInterface
     */
    private $containerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $dummyBase;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->connectionMock = $this
            ->getMockBuilder('ONGR\ElasticsearchBundle\Client\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectorMock = $this
            ->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\MetadataCollector')
            ->disableOriginalConstructor()
            ->getMock();

        $this->containerMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerMock = $this
            ->getMockBuilder('ONGR\ElasticsearchBundle\ORM\Manager')
            ->setConstructorArgs([$this->connectionMock, $this->collectorMock, [], []])
            ->getMock();

        $this->dummyBase = $this
            ->getMockBuilder('ONGR\ElasticsearchBundle\Tests\Unit\Test\ElasticsearchTestCaseDummy')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dummyBase
            ->expects($this->any())
            ->method('getContainer')
            ->will($this->returnValue($this->containerMock));
    }

    /**
     * Tests getting not existing connection.
     */
    public function testGettingNotExistingConnection()
    {
        $this->connectionMock
            ->expects($this->never())
            ->method('dropAndCreateIndex');

        $this->containerMock
            ->expects($this->at(0))
            ->method('has')
            ->with('es.manager.random')
            ->will($this->returnValue(true));

        $this->containerMock
            ->expects($this->at(1))
            ->method('get')
            ->with('es.manager.random')
            ->will($this->returnValue($this->managerMock));

        $reflection = new \ReflectionMethod($this->dummyBase, 'getManager');
        $reflection->setAccessible(true);
        $reflection->invokeArgs($this->dummyBase, ['random', false]);
    }

    /**
     * Tests if exception is thrown.
     *
     * @expectedException \LogicException
     */
    public function testException()
    {
        $this->containerMock
            ->expects($this->at(0))
            ->method('has')
            ->with('es.manager.default')
            ->will($this->returnValue(false));

        $reflection = new \ReflectionMethod($this->dummyBase, 'getManager');
        $reflection->setAccessible(true);
        $reflection->invokeArgs($this->dummyBase, []);
    }

    /**
     * Tests if it sets custom mapping.
     */
    public function testManagerCustomMapping()
    {
        $this->containerMock
            ->expects($this->once())
            ->method('has')
            ->with('es.manager.default')
            ->will($this->returnValue(true));

        $this->containerMock
            ->expects($this->once())
            ->method('get')
            ->with('es.manager.default')
            ->will($this->returnValue($this->managerMock));

        $this->managerMock->expects($this->once())->method('getConnection')->willReturn($this->connectionMock);
        $this->connectionMock->expects($this->once())->method('dropAndCreateIndex')->willReturn(true);

        $reflection = new \ReflectionMethod($this->dummyBase, 'setUp');
        $reflection->setAccessible(true);
        $reflection->invokeArgs($this->dummyBase, []);
    }
}
