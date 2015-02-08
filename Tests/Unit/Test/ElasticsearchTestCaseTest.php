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
     * @var Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connectionMock;

    /**
     * @var MetadataCollector|\PHPUnit_Framework_MockObject_MockObject
     */
    private $collectorMock;

    /**
     * @var Manager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $managerMock;

    /**
     * @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject
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
            ->setMethods(['getVersionNumber', 'dropAndCreateIndex', 'dropIndex'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectorMock = $this
            ->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\MetadataCollector')
            ->disableOriginalConstructor()
            ->getMock();

        $this->containerMock = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerMock = $this
            ->getMockBuilder('ONGR\ElasticsearchBundle\ORM\Manager')
            ->setConstructorArgs([$this->connectionMock, $this->collectorMock, [], []])
            ->getMock();

        $this->dummyBase = $this
            ->getMockBuilder('ONGR\ElasticsearchBundle\Tests\Unit\Test\ElasticsearchTestCaseDummy')
            ->setMethods(['getContainer'])
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

    /**
     * Check if remove manager works as expected.
     *
     * Should drop index and remove the manager.
     */
    public function testRemoveManager()
    {
        $this->containerMock
            ->expects($this->exactly(2))
            ->method('has')
            ->with('es.manager.default')
            ->will($this->returnValue(true));

        $this->containerMock
            ->expects($this->exactly(2))
            ->method('get')
            ->with('es.manager.default')
            ->will($this->returnValue($this->managerMock));

        // GetConnection is called thrice: creating, removing and creating manager again.
        $this->managerMock->expects($this->exactly(3))->method('getConnection')->willReturn($this->connectionMock);
        $this->connectionMock->expects($this->once())->method('dropIndex')->willReturn(true);

        // Calls getConnection once.
        $reflection = new \ReflectionMethod($this->dummyBase, 'setUp');
        $reflection->setAccessible(true);
        $reflection->invokeArgs($this->dummyBase, []);

        // Calls getConnection and dropIndex once.
        $reflection = new \ReflectionMethod($this->dummyBase, 'removeManager');
        $reflection->setAccessible(true);
        $reflection->invokeArgs($this->dummyBase, ['default']);

        // Calls getConnection once, since the default manager should have been removed from cache.
        $reflection = new \ReflectionMethod($this->dummyBase, 'getManager');
        $reflection->setAccessible(true);
        $reflection->invokeArgs($this->dummyBase, []);
    }

    /**
     * Check if managers are cached.
     */
    public function testCache()
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

        $this->managerMock->expects($this->exactly(2))->method('getConnection')->willReturn($this->connectionMock);
        $this->connectionMock->expects($this->exactly(2))->method('dropAndCreateIndex')->willReturn(true);

        // This will call getManager once, should get default manager from container, thus calling get and has.
        $reflection = new \ReflectionMethod($this->dummyBase, 'setUp');
        $reflection->setAccessible(true);
        $reflection->invokeArgs($this->dummyBase, []);

        // Even though getManager is called again, it should be loaded from cache, not from container.
        $reflection = new \ReflectionMethod($this->dummyBase, 'getManager');
        $reflection->setAccessible(true);
        $reflection->invokeArgs($this->dummyBase, []);
    }

    /**
     * Data provider for testIgnoreVersion().
     *
     * @return array
     */
    public function getIgnoreVersionData()
    {
        $out = [];

        // Case #0, version falls within the range, should be skipped.
        $ignoredVersion = [
            ['1.2.5', '='],
            ['1.3.0', '<='],
        ];
        $expectedMessage = 'Elasticsearch version 1.2.0 not supported by this test.';
        $out[] = ['1.2.0', $ignoredVersion, true, $expectedMessage];

        // Case #1, version isn't in the range, should not be skipped.
        $ignoredVersion = [
            ['1.2.5', '='],
            ['1.2.0', '<'],
        ];
        $out[] = ['1.2.0', $ignoredVersion, false];

        // Case #2, nothing is ignored, should not be skipped.
        $out[] = ['1.2.0', [], false];

        // Case #3, version equals the ignored version, should be skipped.
        $ignoredVersion = [
            ['1.2.0', '='],
        ];
        $expectedMessage = 'Elasticsearch version 1.2.0 not supported by this test.';
        $out[] = ['1.2.0', $ignoredVersion, true, $expectedMessage];

        return $out;
    }

    /**
     * Check if ignored version skipping works as expected.
     *
     * @param string $version
     * @param array  $ignoredVersions
     * @param bool   $shouldSkip
     * @param string $expectedMessage
     *
     * @dataProvider getIgnoreVersionData()
     */
    public function testIgnoreVersion($version, $ignoredVersions, $shouldSkip, $expectedMessage = '')
    {
        $actualMessage = '';
        $skipped = false;

        $this->dummyBase = $this
            ->getMockBuilder('ONGR\ElasticsearchBundle\Tests\Unit\Test\ElasticsearchTestCaseDummy')
            ->setMethods(['getIgnoredVersions', 'getContainer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->dummyBase
            ->expects($this->any())
            ->method('getContainer')
            ->will($this->returnValue($this->containerMock));

        $this->dummyBase
            ->expects($this->once())
            ->method('getIgnoredVersions')
            ->willReturn($ignoredVersions);

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
        $this->connectionMock->expects($shouldSkip ? $this->never() : $this->once())->method('dropAndCreateIndex');
        $this->connectionMock->expects($this->once())->method('getVersionNumber')->willReturn($version);

        $reflection = new \ReflectionMethod($this->dummyBase, 'setUp');
        $reflection->setAccessible(true);

        try {
            $reflection->invokeArgs($this->dummyBase, []);
        } catch (\PHPUnit_Framework_SkippedTestError $ex) {
            $actualMessage = $ex->getMessage();
            $skipped = true;
        }

        $this->assertEquals($shouldSkip, $skipped);
        $this->assertEquals($actualMessage, $expectedMessage);
    }
}
