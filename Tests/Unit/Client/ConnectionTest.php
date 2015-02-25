<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Client;

use Elasticsearch\Client;
use ONGR\ElasticsearchBundle\Client\Connection;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests if right values are being taken out.
     */
    public function testGetters()
    {
        $config = [
            'index' => 'index_name',
        ];

        $connection = new Connection($this->getClient(), $config);

        $this->assertEquals(
            'index_name',
            $connection->getIndexName(),
            'Recieved wrong index name'
        );

        $this->assertNull(
            $connection->getMapping(),
            'should return null because no mapping is loaded into connection'
        );

        $connection->setMapping('test_mapping', ['properties' => []]);

        $this->assertEmpty(
            $connection->getMapping('product'),
            'should not contain product mapping and return empty array'
        );

        $this->assertNotEmpty(
            $connection->getMapping('test_mapping'),
            'should contain test mapping'
        );
    }

    /**
     * Tests drop and create index behaviour.
     */
    public function testDropAndCreateIndex()
    {
        $indices = $this
            ->getMockBuilder('Elasticsearch\Namespaces\IndicesNamespace')
            ->disableOriginalConstructor()
            ->getMock();

        $indices
            ->expects($this->once())
            ->method('create')
            ->with(['index' => 'foo', 'body' => []]);

        $indices
            ->expects($this->once())
            ->method('delete')
            ->with(['index' => 'foo']);

        $client = $this
            ->getMockBuilder('Elasticsearch\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $client
            ->expects($this->exactly(2))
            ->method('indices')
            ->will($this->returnValue($indices));

        $connection = new Connection($client, ['index' => 'foo', 'body' => []]);
        $connection->dropAndCreateIndex();
    }

    /**
     * Tests if scroll request is made properly.
     */
    public function testScroll()
    {
        $client = $this->getClient();
        $client->expects($this->once())
            ->method('scroll')
            ->with(['scroll_id' => 'test_id', 'scroll' => '5m'])
            ->willReturn('test');

        $connection = new Connection($client, ['index' => 'foo', 'body' => []]);
        $result = $connection->scroll('test_id', '5m');

        $this->assertEquals('test', $result);
    }

    /**
     * Tests if exception is thown when unknown operation is recieved.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testBulkException()
    {
        $connection = new Connection($this->getClient(), []);
        $connection->bulk('unknownOperation', 'foo_type', []);
    }

    /**
     * Tests flush method behavior.
     */
    public function testFlush()
    {
        $connection = new Connection($this->getClient(['flush']), []);
        $connection->flush();
    }

    /**
     * Tests refresh method behavior.
     */
    public function testRefresh()
    {
        $connection = new Connection($this->getClient(['refresh']), []);
        $connection->refresh();
    }

    /**
     * Tests if the same client is returned.
     */
    public function testGetClient()
    {
        $client = $this
            ->getMockBuilder('Elasticsearch\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $client
            ->expects($this->never())
            ->method($this->anything());

        $hash = spl_object_hash($client);
        $connection = new Connection($client, []);

        $this->assertEquals($hash, spl_object_hash($connection->getClient()));
    }

    /**
     * Tests setBulkParams method.
     */
    public function testSetBulkParams()
    {
        $client = $this->getClient(['flush']);
        $client->expects($this->once())->method('bulk')->with(['refresh' => 'true']);

        $connection = new Connection($client, []);
        $connection->setBulkParams(['refresh' => 'true']);
        $connection->commit();
    }

    /**
     * Tests forceMapping method.
     */
    public function testForceMapping()
    {
        $connection = new Connection($this->getClient(), []);
        $connection->forceMapping(['product' => []]);
        $this->assertEquals([], $connection->getMapping('product'));
    }

    /**
     * Tests setMapping method.
     */
    public function testSetMapping()
    {
        $connection = new Connection($this->getClient(), []);
        $connection->setMapping('product', ['properties' => []]);
        $this->assertArrayHasKey('properties', $connection->getMapping('product'));
    }

    /**
     * Tests if forcing update settings works as expected.
     */
    public function testUpdateSettingsForce()
    {
        $connection = new Connection(
            $this->getClient(),
            [
                'index' => 'foo',
                'body' => [
                    'mappings' => [
                        'foo' => [
                            'properties' => [],
                        ],
                    ],
                ],
            ]
        );

        $this->assertNotEmpty($connection->getMapping('foo'), 'Mapping should exist');

        $connection->updateSettings(['index' => 'foo'], true);

        $this->assertNull($connection->getMapping('foo'), 'Mapping should not exist anymore.');
        $this->assertEquals('foo', $connection->getIndexName(), 'Index name is not correct.');
    }

    /**
     * Data provider for testing setting multiple mapping.
     *
     * @return array
     */
    public function getTestSetMultipleMappingData()
    {
        return [
            // Case #0: no cleanup.
            [
                [
                    'type1' => [
                        'properties' => [],
                    ],
                    'type2' => [
                        'properties' => [],
                    ],
                ],
                ['type1', 'type2', 'oldType1'],
                false,
            ],
            // Case #1: with cleanup.
            [
                [
                    'type1' => [
                        'properties' => [],
                    ],
                ],
                ['type1'],
                true,
            ],
        ];
    }

    /**
     * Tests setting multiple mapping.
     *
     * @param array $mapping
     * @param array $expectedTypes
     * @param bool  $cleanUp
     *
     * @dataProvider getTestSetMultipleMappingData
     */
    public function testSetMultipleMapping($mapping, $expectedTypes, $cleanUp)
    {
        $connection = new Connection(
            $this->getClient(),
            [
                'body' => [
                    'mappings' => [
                        'oldType1' => [
                            'properties' => [],
                        ],
                    ],
                ],
            ]
        );
        $connection->setMultipleMapping($mapping, $cleanUp);

        foreach ($expectedTypes as $expectedType) {
            $map = $connection->getMapping($expectedType);
            $this->assertArrayHasKey('properties', $map);
        }
    }

    /**
     * Tests getMappingFromIndex method when returns empty array.
     */
    public function testGetMappingFromIndex()
    {
        $indices = $this
            ->getMockBuilder('Elasticsearch\Namespaces\IndicesNamespace')
            ->disableOriginalConstructor()
            ->getMock();
        $indices
            ->expects($this->once())
            ->method('getMapping')
            ->with(['index' => 'foo'])
            ->will($this->returnValue(['baz' => []]));

        $client = $this
            ->getMockBuilder('Elasticsearch\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $client
            ->expects($this->any())
            ->method('indices')
            ->will($this->returnValue($indices));

        $connection = new Connection($client, ['index' => 'foo']);
        $this->assertEmpty($connection->getMappingFromIndex());
    }

    /**
     * Tests if exception is thrown with undefined action.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Unknown warmer action
     */
    public function testWarmersActionException()
    {
        $warmerMock = $this->getMock('ONGR\ElasticsearchBundle\Cache\WarmerInterface');
        $warmerMock
            ->expects($this->once())
            ->method('warmUp');
        $warmerMock
            ->expects($this->once())
            ->method('getName');

        $connection = new Connection($this->getClient(), []);
        $connection->addWarmer($warmerMock);

        $object = new \ReflectionObject($connection);
        $method = $object->getMethod('warmersAction');
        $method->setAccessible(true);
        $method->invokeArgs($connection, ['undefined']);
    }

    /**
     * Tests if exception is thrown while validating warmers.
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Warmer(s) named bar do not exist. Available: foo
     */
    public function testValidateWarmersException()
    {
        $warmerMock = $this->getMock('ONGR\ElasticsearchBundle\Cache\WarmerInterface');
        $warmerMock
            ->expects($this->once())
            ->method('warmUp');
        $warmerMock
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('foo'));

        $connection = new Connection($this->getClient(), []);
        $connection->addWarmer($warmerMock);

        $object = new \ReflectionObject($connection);
        $method = $object->getMethod('validateWarmers');
        $method->setAccessible(true);
        $method->invokeArgs($connection, [['bar']]);
    }

    /**
     * Tests Connection#setIndexName method.
     */
    public function testSetIndexName()
    {
        $connection = new Connection($this->getClient(), ['index' => 'foo']);
        $this->assertEquals('foo', $connection->getIndexName(), 'Index name should not be changed.');
        $connection->setIndexName('bar');
        $this->assertEquals('bar', $connection->getIndexName(), 'Index name should be changed');
    }

    /**
     * Returns client instance with indices namespace set.
     *
     * @param array $options
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Client
     */
    private function getClient(array $options = [])
    {
        $indices = $this
            ->getMockBuilder('Elasticsearch\Namespaces\IndicesNamespace')
            ->disableOriginalConstructor()
            ->getMock();
        $indices
            ->expects(in_array('refresh', $options) ? $this->once() : $this->never())
            ->method('refresh');
        $indices
            ->expects(in_array('flush', $options) ? $this->once() : $this->never())
            ->method('flush');

        $client = $this
            ->getMockBuilder('Elasticsearch\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $client
            ->expects($this->any())
            ->method('indices')
            ->will($this->returnValue($indices));

        return $client;
    }
}
